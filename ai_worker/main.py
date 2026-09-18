import cv2
import requests
from ultralytics import YOLO
import time
import os

# Konfigurasi
AMS_URL = "http://ams.ciamiskab.go.id:5443/LiveApp/streams/117282772591605481358908.m3u8" # Ganti dengan streamId yang sesuai
API_URL = "https://api.atcs.ciamiskab.go.id/api/traffic-logs" # Ganti dengan URL API Laravel nanti
CAMERA_ID = 1

def main():
    print("Memuat Model YOLOv8...")
    model = YOLO("yolov8n.pt") # Gunakan nano model untuk performa CPU yang lebih baik
    
    print(f"Membuka stream: {AMS_URL}")
    cap = cv2.VideoCapture(AMS_URL)
    
    if not cap.isOpened():
        print("Gagal membuka stream video!")
        return

    frame_count = 0
    last_api_send = time.time()

    while True:
        ret, frame = cap.read()
        if not ret:
            print("Stream terputus. Mencoba reconnect...")
            cap.release()
            time.sleep(5)
            cap = cv2.VideoCapture(AMS_URL)
            continue
            
        frame_count += 1
        
        # Proses setiap 10 frame saja untuk menghemat CPU
        if frame_count % 10 != 0:
            continue
            
        # Jalankan deteksi YOLO
        results = model(frame, classes=[2, 3, 5, 7], verbose=False) # 2: car, 3: motorcycle, 5: bus, 7: truck
        
        car_count = 0
        motorcycle_count = 0
        
        for r in results:
            boxes = r.boxes
            for box in boxes:
                cls_id = int(box.cls[0])
                if cls_id == 2: # Car
                    car_count += 1
                elif cls_id == 3: # Motorcycle
                    motorcycle_count += 1
                    
        # Kirim data ke API setiap 60 detik
        current_time = time.time()
        if current_time - last_api_send >= 60:
            payload = {
                "cctv_id": CAMERA_ID,
                "car_count": car_count,
                "motorcycle_count": motorcycle_count,
                "timestamp": int(current_time)
            }
            try:
                # requests.post(API_URL, json=payload, timeout=5)
                print(f"[API UPDATE] CCTV {CAMERA_ID} -> Mobil: {car_count}, Motor: {motorcycle_count}")
            except Exception as e:
                print(f"Gagal mengirim data API: {e}")
                
            last_api_send = current_time

if __name__ == "__main__":
    main()
