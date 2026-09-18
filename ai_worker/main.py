import cv2
import requests
from ultralytics import YOLO
import time
import subprocess
import threading

# --- KONFIGURASI ---
SOURCE_STREAM = "http://ams.ciamiskab.go.id:5443/LiveApp/streams/117282772591605481358908.m3u8"

# Beri nama CCTV agar mudah dibaca di Ant Media Server (Tanpa Spasi)
CAMERA_NAME = "Simpang_Tonjong"

# Menambahkan akhiran _ai dan mengarahkannya ke aplikasi /live
TARGET_RTMP = f"rtmp://ams.ciamiskab.go.id/live/{CAMERA_NAME}_ai"

API_URL = "https://api.atcs.ciamiskab.go.id/api/traffic-logs"
CAMERA_ID = 1

def main():
    print("Memuat Model YOLOv8...")
    model = YOLO("yolov8n.pt") 
    
    print(f"Membuka sumber video: {SOURCE_STREAM}")
    cap = cv2.VideoCapture(SOURCE_STREAM)
    
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
            cap = cv2.VideoCapture(SOURCE_STREAM)
            continue
            
        # Jalankan deteksi YOLO (verbose=False agar terminal rapi)
        results = model(frame, classes=[2, 3, 5, 7], verbose=False)
        
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
