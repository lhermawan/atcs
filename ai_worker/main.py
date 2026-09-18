import cv2
import requests
from ultralytics import YOLO
import time
import subprocess
import threading

# --- KONFIGURASI DASAR ---
AMS_API_URL = "https://ams.ciamiskab.go.id:5443/LiveApp/rest/v2/broadcasts/list/0/50"
API_URL = "https://api.atcs.ciamiskab.go.id/api/traffic-logs"
TARGET_CAMERA = "Simpang Tonjong Arah Tyara" # Kosongkan ("") jika ingin otomatis memilih CCTV pertama yang nyala

def get_active_stream():
    """Mengambil satu CCTV dari API Ant Media Server"""
    try:
        response = requests.get(AMS_API_URL, verify=False, timeout=10)
        cctvs = response.json()
        
        # Jika mencari kamera spesifik
        if TARGET_CAMERA:
            for cctv in cctvs:
                if cctv.get("status") == "broadcasting" and cctv.get("name") == TARGET_CAMERA:
                    return cctv.get("streamId"), cctv.get("name")
                    
        # Fallback: Ambil yang mana saja yang sedang LIVE
        for cctv in cctvs:
            if cctv.get("status") == "broadcasting":
                return cctv.get("streamId"), cctv.get("name")
                
    except Exception as e:
        print(f"Gagal mengambil API AMS: {e}")
    return None, None

def main():
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    print("Mencari CCTV yang sedang LIVE...")
    stream_id, camera_name = get_active_stream()
    
    if not stream_id:
        print("Tidak ada CCTV yang sedang LIVE saat ini!")
        return
        
    # Bersihkan nama kamera dari spasi agar aman untuk RTMP
    clean_name = camera_name.replace(" ", "_").replace("-", "_")
    
    SOURCE_STREAM = f"https://ams.ciamiskab.go.id:5443/LiveApp/streams/{stream_id}.m3u8"
    TARGET_RTMP = f"rtmp://ams.ciamiskab.go.id/live/{clean_name}_ai"
    
    print(f"[{camera_name}] Ditemukan! Stream ID: {stream_id}")
    print("Memuat Model YOLOv8s (Small) untuk akurasi lebih baik...")
    model = YOLO("yolov8s.pt") 
    
    print(f"Membuka sumber video: {SOURCE_STREAM}")
    cap = cv2.VideoCapture(SOURCE_STREAM, cv2.CAP_FFMPEG)
    
    if not cap.isOpened():
        print("Gagal membuka stream video!")
        return

    # Ambil resolusi dan FPS asli
    width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
    fps = int(cap.get(cv2.CAP_PROP_FPS))
    if fps == 0 or fps > 60: fps = 25 # Fallback

    print(f"Resolusi Stream: {width}x{height} @ {fps}fps")
    
    # Setup FFmpeg untuk Push RTMP
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
            
        # Jalankan deteksi YOLO dengan minimal keyakinan 30% (conf=0.3)
        results = model(frame, classes=[2, 3, 5, 7], conf=0.3, verbose=False)
        
        car_count = 0
        motorcycle_count = 0
        
        for r in results:
            boxes = r.boxes
            for box in boxes:
                cls_id = int(box.cls[0])
                conf = float(box.conf[0])
                
                # Gambar Bounding Box
                x1, y1, x2, y2 = map(int, box.xyxy[0])
                color = (0, 255, 0) # Hijau default
                
                if cls_id == 2:
                    car_count += 1
                    color = (255, 0, 0) # Biru untuk mobil
                    label = f"Mobil {conf:.2f}"
                elif cls_id == 3:
                    motorcycle_count += 1
                    color = (0, 255, 255) # Kuning untuk motor
                    label = f"Motor {conf:.2f}"
                elif cls_id in [5, 7]:
                    color = (0, 0, 255) # Merah untuk truk/bus
                    label = f"Besar {conf:.2f}"
                    
                cv2.rectangle(frame, (x1, y1), (x2, y2), color, 2)
                cv2.putText(frame, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.5, color, 2)
                
        # Gambar Counter di layar
        cv2.putText(frame, f"Mobil: {car_count}", (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 3)
        cv2.putText(frame, f"Motor: {motorcycle_count}", (20, 80), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 3)
        cv2.putText(frame, "LIVE - AI PENDETEKSI KENDARAAN", (20, 120), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)

        # Lempar frame yang sudah digambar ke FFmpeg
        try:
            process.stdin.write(frame.tobytes())
        except Exception as e:
            print("FFmpeg error/terputus. Restarting...")
            process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
            
        # Kirim API
        current_time = time.time()
        if current_time - last_api_send >= 60:
            print(f"[API UPDATE] Mobil: {car_count}, Motor: {motorcycle_count}")
            last_api_send = current_time

if __name__ == "__main__":
    main()
