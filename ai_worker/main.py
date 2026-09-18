import cv2
import requests
from ultralytics import YOLO
import time
import subprocess
import threading
import sys
import os

# --- KONFIGURASI DASAR ---
AMS_API_URL = "http://127.0.0.1:5080/LiveApp/rest/v2/broadcasts/list/0/50"
API_URL = "https://api.atcs.ciamiskab.go.id/api/traffic-logs"
CONFIG_API_URL = "https://api.atcs.ciamiskab.go.id/api/ai-config"

# Mengambil konfigurasi awal dari Laravel
def get_target_camera():
    try:
        import urllib3
        urllib3.disable_warnings()
        response = requests.get(CONFIG_API_URL, verify=False, timeout=5)
        if response.status_code == 200:
            return response.json().get('target_camera', None)
    except Exception as e:
        print(f"Gagal memanggil API Config: {e}")
    return None

TARGET_CAMERA = get_target_camera()

# Variabel Global
latest_frame = None
latest_boxes = []
latest_counts = {"car": 0, "motorcycle": 0} # Total kumulatif
interval_counts = {"car": 0, "motorcycle": 0} # Direset tiap 60 detik untuk dikirim ke web
line_y = 300 # Default, akan diupdate otomatis jadi setengah layar
lock = threading.Lock()

# Pelacakan (Tracking)
tracked_objects = {}
crossed_ids = set()

def get_active_stream():
    try:
        response = requests.get(AMS_API_URL, verify=False, timeout=10)
        cctvs = response.json()
        
        if TARGET_CAMERA:
            for cctv in cctvs:
                if cctv.get("status") == "broadcasting" and cctv.get("name") == TARGET_CAMERA:
                    return cctv.get("streamId"), cctv.get("name")
                    
        for cctv in cctvs:
            if cctv.get("status") == "broadcasting":
                return cctv.get("streamId"), cctv.get("name")
    except Exception as e:
        print(f"Gagal mengambil API AMS: {e}")
    return None, None

def yolo_worker(model):
    global latest_frame, latest_boxes, latest_counts, interval_counts, line_y, tracked_objects, crossed_ids
    
    while True:
        frame_to_process = None
        with lock:
            if latest_frame is not None:
                frame_to_process = latest_frame.copy()
                
        if frame_to_process is not None:
            # Gunakan mode TRACKING (ByteTrack)
            results = model.track(frame_to_process, classes=[2, 3, 5, 7], conf=0.45, persist=True, tracker="bytetrack.yaml", verbose=False)
            
            temp_boxes = []
            
            for r in results:
                boxes = r.boxes
                if boxes.id is not None:
                    ids = boxes.id.cpu().numpy().astype(int)
                    for box, track_id in zip(boxes, ids):
                        cls_id = int(box.cls[0])
                        conf = float(box.conf[0])
                        x1, y1, x2, y2 = map(int, box.xyxy[0])
                        
                        cy = (y1 + y2) // 2 # Titik tengah bawah objek
                        
                        color = (0, 255, 0)
                        label = f"ID:{track_id} {conf:.2f}"
                        
                        # Cek apakah melewati garis
                        if track_id in tracked_objects:
                            prev_cy = tracked_objects[track_id]
                            # Jika menyeberangi garis (atas ke bawah atau bawah ke atas)
                            if (prev_cy < line_y and cy >= line_y) or (prev_cy > line_y and cy <= line_y):
                                if track_id not in crossed_ids:
                                    crossed_ids.add(track_id)
                                    with lock:
                                        if cls_id == 2 or cls_id in [5,7]:
                                            latest_counts["car"] += 1
                                            interval_counts["car"] += 1
                                        elif cls_id == 3:
                                            latest_counts["motorcycle"] += 1
                                            interval_counts["motorcycle"] += 1
                        
                        tracked_objects[track_id] = cy
                        
                        if cls_id == 2:
                            color = (255, 0, 0)
                            label = f"Mobil #{track_id}"
                        elif cls_id == 3:
                            color = (0, 255, 255)
                            label = f"Motor #{track_id}"
                        elif cls_id in [5, 7]:
                            color = (0, 0, 255)
                            label = f"Besar #{track_id}"
                            
                        # Warnai objek yang sudah dihitung jadi hijau terang
                        if track_id in crossed_ids:
                            color = (0, 255, 0)
                            
                        temp_boxes.append((x1, y1, x2, y2, color, label))
            
            with lock:
                latest_boxes = temp_boxes
                
        time.sleep(0.01)

def main():
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    global latest_frame, latest_boxes, latest_counts, interval_counts, line_y
    
    print(f"Target Kamera dari Laravel: {TARGET_CAMERA}")
    print("Mencari CCTV yang sedang LIVE...")
    stream_id, camera_name = get_active_stream()
    
    if not stream_id:
        print("Tidak ada CCTV yang sedang LIVE saat ini! Menunggu 10 detik...")
        time.sleep(10)
        os.execv(sys.executable, ['python'] + sys.argv)
        return
        
    clean_name = camera_name.replace(" ", "_").replace("-", "_")
    SOURCE_STREAM = f"http://127.0.0.1:5080/LiveApp/streams/{stream_id}.m3u8"
    TARGET_RTMP = f"rtmp://127.0.0.1/live/{clean_name}_ai"
    
    print(f"[{camera_name}] Ditemukan! Stream ID: {stream_id}")
    print("Memuat Model YOLOv8s (Small)...")
    model = YOLO("yolov8s.pt") 
    
    print(f"Membuka sumber video: {SOURCE_STREAM}")
    cap = cv2.VideoCapture(SOURCE_STREAM, cv2.CAP_FFMPEG)
    
    if not cap.isOpened():
        print("Gagal membuka stream video! Restarting...")
        time.sleep(5)
        os.execv(sys.executable, ['python'] + sys.argv)
        return

    width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    fps = int(cap.get(cv2.CAP_PROP_FPS))
    if fps == 0 or fps > 60: fps = 25
    
    line_y = int(height * 0.6) # Garis ditempatkan di 60% layar dari atas

    print(f"Resolusi Stream: {width}x{height} @ {fps}fps")
    
    # Jalankan thread YOLO di background
    yolo_thread = threading.Thread(target=yolo_worker, args=(model,), daemon=True)
    yolo_thread.start()
    
    ffmpeg_cmd = [
        'ffmpeg',
        '-y',
        '-f', 'rawvideo',
        '-vcodec', 'rawvideo',
        '-pix_fmt', 'bgr24',
        '-s', f"{width}x{height}",
        '-r', str(fps),
        '-i', '-',
        '-c:v', 'libx264',
        '-preset', 'veryfast',
        '-maxrate', '1500k',
        '-bufsize', '3000k',
        '-pix_fmt', 'yuv420p',
        '-f', 'flv',
        TARGET_RTMP
    ]
    
    print(f"Memulai transmisi ke: {TARGET_RTMP}")
    process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
    
    last_api_send = time.time()
    last_config_check = time.time()
    
    while True:
        ret, frame = cap.read()
        if not ret:
            print("Stream terputus. Restarting script...")
            cap.release()
            os.execv(sys.executable, ['python'] + sys.argv)
            continue
            
        with lock:
            latest_frame = frame
            current_boxes = list(latest_boxes)
            car_total = latest_counts["car"]
            motor_total = latest_counts["motorcycle"]
            
        # Gambar Garis Perhitungan
        cv2.line(frame, (0, line_y), (width, line_y), (0, 0, 255), 2)
        cv2.putText(frame, "GARIS PERHITUNGAN", (10, line_y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, (0, 0, 255), 2)
            
        for (x1, y1, x2, y2, color, label) in current_boxes:
            cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)
            cv2.putText(frame, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)
                
        # Gambar Counter Total di layar
        cv2.putText(frame, f"Total Mobil: {car_total}", (20, 80), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 3)
        cv2.putText(frame, f"Total Motor: {motor_total}", (20, 120), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 3)
        cv2.putText(frame, "LIVE - AI KENDARAAN (TRACKING MODE)", (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)

        try:
            process.stdin.write(frame.tobytes())
        except Exception as e:
            process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
            
        current_time = time.time()
        
        # Kirim API tiap 60 detik (Interval Data)
        if current_time - last_api_send >= 60:
            with lock:
                sent_car = interval_counts["car"]
                sent_motor = interval_counts["motorcycle"]
                interval_counts["car"] = 0
                interval_counts["motorcycle"] = 0
                
            print(f"[API UPDATE] Mobil Lewat: {sent_car}, Motor Lewat: {sent_motor}")
            payload = {
                "stream_id": stream_id,
                "camera_name": camera_name,
                "car_count": sent_car,
                "motorcycle_count": sent_motor
            }
            try:
                requests.post(API_URL, json=payload, verify=False, timeout=5)
            except Exception as e:
                pass
            last_api_send = current_time
            
        # Cek Perubahan Konfigurasi dari Laravel tiap 20 detik
        if current_time - last_config_check >= 20:
            new_target = get_target_camera()
            if new_target and new_target != TARGET_CAMERA:
                print(f"!!! Perintah dari Admin: Pindah target ke '{new_target}' !!!")
                print("Restarting worker...")
                cap.release()
                process.kill()
                os.execv(sys.executable, ['python'] + sys.argv)
            last_config_check = current_time

if __name__ == "__main__":
    main()
