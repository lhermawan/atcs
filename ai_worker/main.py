import cv2
import requests
from ultralytics import YOLO
import time
import subprocess
import threading
import numpy as np

# --- KONFIGURASI DASAR ---
# Gunakan localhost (127.0.0.1) karena AI dan Ant Media berada di server yang sama
AMS_API_URL = "http://127.0.0.1:5080/LiveApp/rest/v2/broadcasts/list/0/50"
API_URL = "https://api.atcs.ciamiskab.go.id/api/traffic-logs"
TARGET_CAMERA = "Simpang Kodim Arah Banjar" 

# Variabel Global untuk Threading
latest_frame = None
latest_boxes = []
latest_counts = {"car": 0, "motorcycle": 0}
lock = threading.Lock()

def get_active_stream():
    try:
        import urllib3
        urllib3.disable_warnings()
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
    """Thread terpisah untuk memproses YOLO agar tidak membuat video lag/patah-patah"""
    global latest_frame, latest_boxes, latest_counts
    
    while True:
        frame_to_process = None
        with lock:
            if latest_frame is not None:
                frame_to_process = latest_frame.copy()
                
        if frame_to_process is not None:
            # Tingkatkan batas keyakinan (conf) menjadi 45% untuk mengurangi halusinasi di malam hari
            results = model(frame_to_process, classes=[2, 3, 5, 7], conf=0.45, verbose=False)
            
            temp_boxes = []
            car_count = 0
            motorcycle_count = 0
            
            for r in results:
                boxes = r.boxes
                for box in boxes:
                    cls_id = int(box.cls[0])
                    conf = float(box.conf[0])
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    
                    color = (0, 255, 0)
                    label = f"Obj {conf:.2f}"
                    
                    if cls_id == 2:
                        car_count += 1
                        color = (255, 0, 0)
                        label = f"Mobil {conf:.2f}"
                    elif cls_id == 3:
                        motorcycle_count += 1
                        color = (0, 255, 255)
                        label = f"Motor {conf:.2f}"
                    elif cls_id in [5, 7]:
                        color = (0, 0, 255)
                        label = f"Besar {conf:.2f}"
                        
                    temp_boxes.append((x1, y1, x2, y2, color, label))
            
            with lock:
                latest_boxes = temp_boxes
                latest_counts["car"] = car_count
                latest_counts["motorcycle"] = motorcycle_count
                
        time.sleep(0.01)

def main():
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    global latest_frame, latest_boxes, latest_counts
    
    print("Mencari CCTV yang sedang LIVE...")
    stream_id, camera_name = get_active_stream()
    
    if not stream_id:
        print("Tidak ada CCTV yang sedang LIVE saat ini!")
        return
        
    clean_name = camera_name.replace(" ", "_").replace("-", "_")
    SOURCE_STREAM = f"http://127.0.0.1:5080/LiveApp/streams/{stream_id}.m3u8"
    TARGET_RTMP = f"rtmp://127.0.0.1/live/{clean_name}_ai"
    
    print(f"[{camera_name}] Ditemukan! Stream ID: {stream_id}")
    print("Memuat Model YOLOv8s (Small)...")
    model = YOLO("yolov8s.pt") 
    
    yolo_thread = threading.Thread(target=yolo_worker, args=(model,), daemon=True)
    yolo_thread.start()
    print("YOLO Worker thread started.")
    
    print(f"Membuka sumber video: {SOURCE_STREAM}")
    cap = cv2.VideoCapture(SOURCE_STREAM, cv2.CAP_FFMPEG)
    
    if not cap.isOpened():
        print("Gagal membuka stream video!")
        return

    width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    fps = int(cap.get(cv2.CAP_PROP_FPS))
    if fps == 0 or fps > 60: fps = 25

    print(f"Resolusi Stream: {width}x{height} @ {fps}fps")
    
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
    
    while True:
        ret, frame = cap.read()
        if not ret:
            print("Stream terputus. Mencoba reconnect...")
            cap.release()
            time.sleep(5)
            cap = cv2.VideoCapture(SOURCE_STREAM, cv2.CAP_FFMPEG)
            continue
            
        with lock:
            latest_frame = frame
            current_boxes = list(latest_boxes)
            car_count = latest_counts["car"]
            motorcycle_count = latest_counts["motorcycle"]
            
        for (x1, y1, x2, y2, color, label) in current_boxes:
            cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)
            cv2.putText(frame, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)
                
        # Gambar Counter di layar (Digeser ke bawah agar tidak tertimpa jam CCTV)
        cv2.putText(frame, f"Mobil: {car_count}", (20, 100), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 3)
        cv2.putText(frame, f"Motor: {motorcycle_count}", (20, 140), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 3)
        cv2.putText(frame, "LIVE - AI PENDETEKSI KENDARAAN", (20, 180), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)

        # Lempar frame secara mulus 25 FPS ke FFmpeg
        try:
            process.stdin.write(frame.tobytes())
        except Exception as e:
            print("FFmpeg error/terputus. Restarting...")
            process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
            
        # Kirim API tiap 60 detik
        current_time = time.time()
        if current_time - last_api_send >= 60:
            print(f"[API UPDATE] Mobil: {car_count}, Motor: {motorcycle_count}")
            payload = {
                "stream_id": stream_id,
                "camera_name": camera_name,
                "car_count": car_count,
                "motorcycle_count": motorcycle_count
            }
            try:
                # Disini kita pastikan diarahkan ke https://api.atcs.ciamiskab.go.id atau yang sesuai
                requests.post(API_URL, json=payload, verify=False, timeout=5)
            except Exception as e:
                print(f"Gagal mengirim ke API Laravel: {e}")
                
            last_api_send = current_time

if __name__ == "__main__":
    main()
