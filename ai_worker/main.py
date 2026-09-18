import cv2
import requests
from ultralytics import YOLO
import time
import subprocess
import threading
import sys
import os
import numpy as np
import supervision as sv

# --- KONFIGURASI DASAR ---
AMS_API_URL = "http://192.168.122.1:5080/LiveApp/rest/v2/broadcasts/list/0/50"
API_URL = "https://atcs.ciamiskab.go.id/api/traffic-logs"
CONFIG_API_URL = "https://atcs.ciamiskab.go.id/api/ai-config"

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
latest_detections = None
latest_counts = {"car": 0, "motorcycle": 0}
interval_counts = {"car": 0, "motorcycle": 0}
lock = threading.Lock()

from trackers import ByteTrackTracker

# Supervision Setup
tracker = ByteTrackTracker()
line_zone = None

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
    global latest_frame, latest_detections, latest_counts, interval_counts, line_zone
    
    while True:
        frame_to_process = None
        with lock:
            if latest_frame is not None:
                frame_to_process = latest_frame.copy()
                
        if frame_to_process is not None and line_zone is not None:
            # Jalankan model YOLO biasa
            results = model(frame_to_process, classes=[2, 3, 5, 7], conf=0.45, verbose=False)[0]
            
            # Konversi hasil YOLO ke Supervision Detections
            detections = sv.Detections.from_ultralytics(results)
            
            # Update Tracker dengan Detections dari package baru
            detections = tracker.update(detections)
            
            # Memicu Line Zone (Hitung kendaraan yg lewat)
            crossed_in, crossed_out = line_zone.trigger(detections)
            
            # Hitung kategori spesifik
            temp_car = 0
            temp_motor = 0
            
            for i, (is_in, is_out) in enumerate(zip(crossed_in, crossed_out)):
                if is_in or is_out:
                    cls_id = detections.class_id[i]
                    if cls_id == 2 or cls_id in [5,7]:
                        temp_car += 1
                    elif cls_id == 3:
                        temp_motor += 1
                        
            with lock:
                latest_detections = detections
                if temp_car > 0:
                    latest_counts["car"] += temp_car
                    interval_counts["car"] += temp_car
                if temp_motor > 0:
                    latest_counts["motorcycle"] += temp_motor
                    interval_counts["motorcycle"] += temp_motor
                
        time.sleep(0.01)

def main():
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    global latest_frame, latest_detections, latest_counts, interval_counts, line_zone
    
    print(f"Target Kamera dari Laravel: {TARGET_CAMERA}")
    print("Mencari CCTV yang sedang LIVE...")
    stream_id, camera_name = get_active_stream()
    
    if not stream_id:
        print("Tidak ada CCTV yang sedang LIVE saat ini! Menunggu 10 detik...")
        time.sleep(10)
        os.execv(sys.executable, ['python'] + sys.argv)
        return
        
    clean_name = camera_name.replace(" ", "_").replace("-", "_")
    SOURCE_STREAM = f"http://192.168.122.1:5080/LiveApp/streams/{stream_id}.m3u8"
    TARGET_RTMP = f"rtmp://192.168.122.1/live/{clean_name}_ai"
    
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
    
    # Inisialisasi Line Zone di 60% layar
    start = sv.Point(0, int(height * 0.6))
    end = sv.Point(width, int(height * 0.6))
    line_zone = sv.LineZone(start=start, end=end)
    
    # Setup Annotator Supervision
    box_annotator = sv.BoxAnnotator(thickness=2)
    label_annotator = sv.LabelAnnotator(text_thickness=1, text_scale=0.5)
    line_zone_annotator = sv.LineZoneAnnotator(thickness=2, text_thickness=2, text_scale=1)

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
            current_detections = latest_detections
            car_total = latest_counts["car"]
            motor_total = latest_counts["motorcycle"]
            
        # Gambar kotak, label, dan garis menggunakan Supervision Annotator
        if current_detections is not None:
            # Custom labels untuk mengganti nama class bahasa inggris ke indonesia
            labels = []
            for class_id, tracker_id in zip(current_detections.class_id, current_detections.tracker_id):
                if class_id == 2: name = "Mobil"
                elif class_id == 3: name = "Motor"
                else: name = "Kend. Besar"
                labels.append(f"#{tracker_id} {name}")
                
            frame = box_annotator.annotate(scene=frame, detections=current_detections)
            frame = label_annotator.annotate(scene=frame, detections=current_detections, labels=labels)
            
        frame = line_zone_annotator.annotate(frame, line_counter=line_zone)
        
        # Gambar Counter Total di layar
        cv2.putText(frame, f"Total Mobil (Lewat Garis): {car_total}", (20, 80), cv2.FONT_HERSHEY_SIMPLEX, 1, (255, 0, 0), 3)
        cv2.putText(frame, f"Total Motor (Lewat Garis): {motor_total}", (20, 120), cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 255), 3)
        cv2.putText(frame, "LIVE - ROBOFLOW SUPERVISION TRACKING", (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)

        try:
            process.stdin.write(frame.tobytes())
        except Exception as e:
            process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
            
        current_time = time.time()
        
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
