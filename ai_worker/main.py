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
from trackers import ByteTrackTracker

# --- KONFIGURASI DASAR ---
AMS_API_URL = "http://192.168.122.1:5080/LiveApp/rest/v2/broadcasts/list/0/50"
API_URL = "https://atcs.ciamiskab.go.id/api/traffic-logs"
CONFIG_API_URL = "https://atcs.ciamiskab.go.id/api/ai-config"

def get_ai_config():
    try:
        import urllib3
        urllib3.disable_warnings()
        response = requests.get(CONFIG_API_URL, verify=False, timeout=5)
        if response.status_code == 200:
            return response.json()
    except Exception as e:
        print(f"Gagal memanggil API Config: {e}")
    return {}

config = get_ai_config()
TARGET_CAMERA = config.get('target_camera')
LINE_POS = config.get('line_position', 60)
LINE_DIR = config.get('line_direction', 'normal')

# Variabel Global
latest_frame = None
latest_detections = None
latest_counts = {"car_in": 0, "car_out": 0, "motor_in": 0, "motor_out": 0}
interval_counts = {"car_in": 0, "car_out": 0, "motor_in": 0, "motor_out": 0}
lock = threading.Lock()

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
            results = model(frame_to_process, classes=[2, 3, 5, 7], conf=0.25, verbose=False)[0]
            detections = sv.Detections.from_ultralytics(results)
            tracked_detections = tracker.update(detections)
            
            if len(tracked_detections) > 0:
                crossed_in, crossed_out = line_zone.trigger(tracked_detections)
                
                temp_car_in = 0
                temp_car_out = 0
                temp_motor_in = 0
                temp_motor_out = 0
                
                for i, (is_in, is_out) in enumerate(zip(crossed_in, crossed_out)):
                    cls_id = tracked_detections.class_id[i]
                    
                    # Logika arah (Kiri ke Kanan -> is_in = Atas ke Bawah, is_out = Bawah ke Atas)
                    actual_in = is_out if LINE_DIR == 'swapped' else is_in
                    actual_out = is_in if LINE_DIR == 'swapped' else is_out
                    
                    if actual_in:
                        if cls_id == 2 or cls_id in [5,7]: temp_car_in += 1
                        elif cls_id == 3: temp_motor_in += 1
                    if actual_out:
                        if cls_id == 2 or cls_id in [5,7]: temp_car_out += 1
                        elif cls_id == 3: temp_motor_out += 1
                            
                with lock:
                    latest_detections = tracked_detections
                    latest_counts["car_in"] += temp_car_in
                    interval_counts["car_in"] += temp_car_in
                    
                    latest_counts["car_out"] += temp_car_out
                    interval_counts["car_out"] += temp_car_out
                    
                    latest_counts["motor_in"] += temp_motor_in
                    interval_counts["motor_in"] += temp_motor_in
                    
                    latest_counts["motor_out"] += temp_motor_out
                    interval_counts["motor_out"] += temp_motor_out
            else:
                with lock:
                    latest_detections = tracked_detections
                
        time.sleep(0.01)

def main():
    import urllib3
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
    
    global latest_frame, latest_detections, latest_counts, interval_counts, line_zone
    
    print(f"Target Kamera: {TARGET_CAMERA}")
    print(f"Posisi Garis: {LINE_POS}% | Arah: {LINE_DIR}")
    
    stream_id, camera_name = get_active_stream()
    if not stream_id:
        print("Tidak ada CCTV yang sedang LIVE! Menunggu 10 detik...")
        time.sleep(10)
        os.execv(sys.executable, ['python'] + sys.argv)
        return
        
    clean_name = camera_name.replace(" ", "_").replace("-", "_")
    SOURCE_STREAM = f"http://192.168.122.1:5080/LiveApp/streams/{stream_id}.m3u8"
    TARGET_RTMP = f"rtmp://192.168.122.1/live/{clean_name}_ai"
    
    print(f"[{camera_name}] Ditemukan! Stream ID: {stream_id}")
    model = YOLO("yolov8s.pt") 
    
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
    
    # Inisialisasi Line Zone selalu konstan (Kiri ke Kanan) agar posisi visual tidak meloncat
    y_pos = int(height * (LINE_POS / 100.0))
    start = sv.Point(0, y_pos)
    end = sv.Point(width, y_pos)
        
    line_zone = sv.LineZone(start=start, end=end)
    
    # Kustomisasi teks yang menempel di garis
    in_label = "OUT" if LINE_DIR == 'swapped' else "IN"
    out_label = "IN" if LINE_DIR == 'swapped' else "OUT"
    
    box_annotator = sv.BoxAnnotator(thickness=2)
    label_annotator = sv.LabelAnnotator(text_thickness=1, text_scale=0.5)
    line_zone_annotator = sv.LineZoneAnnotator(
        thickness=2, 
        text_thickness=2, 
        text_scale=1,
        custom_in_text=in_label,
        custom_out_text=out_label
    )

    yolo_thread = threading.Thread(target=yolo_worker, args=(model,), daemon=True)
    yolo_thread.start()
    
    ffmpeg_cmd = [
        'ffmpeg', '-y', '-f', 'rawvideo', '-vcodec', 'rawvideo',
        '-pix_fmt', 'bgr24', '-s', f"{width}x{height}", '-r', str(fps),
        '-i', '-', '-c:v', 'libx264', '-preset', 'veryfast',
        '-maxrate', '1500k', '-bufsize', '3000k', '-pix_fmt', 'yuv420p',
        '-f', 'flv', TARGET_RTMP
    ]
    process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
    
    last_api_send = time.time()
    last_config_check = time.time()
    frame_delay = 1.0 / fps
    
    while True:
        loop_start = time.time()
        
        ret, frame = cap.read()
        if not ret:
            print("Stream terputus. Restarting script...")
            cap.release()
            os.execv(sys.executable, ['python'] + sys.argv)
            continue
            
        with lock:
            latest_frame = frame
            current_detections = latest_detections
            cin = latest_counts["car_in"]
            cout = latest_counts["car_out"]
            min = latest_counts["motor_in"]
            mout = latest_counts["motor_out"]
            
        if current_detections is not None and len(current_detections) > 0:
            labels = []
            for i in range(len(current_detections)):
                class_id = current_detections.class_id[i]
                tracker_id = current_detections.tracker_id[i] if current_detections.tracker_id is not None else "..."
                if class_id == 2: name = "Mobil"
                elif class_id == 3: name = "Motor"
                else: name = "Kend. Besar"
                labels.append(f"#{tracker_id} {name}")
                
            frame = box_annotator.annotate(scene=frame, detections=current_detections)
            frame = label_annotator.annotate(scene=frame, detections=current_detections, labels=labels)
            
        frame = line_zone_annotator.annotate(frame, line_counter=line_zone)
        
        cv2.putText(frame, f"Mobil IN: {cin} | OUT: {cout}", (20, 80), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 0, 0), 2)
        cv2.putText(frame, f"Motor IN: {min} | OUT: {mout}", (20, 120), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 255), 2)
        cv2.putText(frame, "LIVE - ROBOFLOW SUPERVISION TRACKING", (20, 40), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)

        try:
            process.stdin.write(frame.tobytes())
        except Exception as e:
            process = subprocess.Popen(ffmpeg_cmd, stdin=subprocess.PIPE)
            
        current_time = time.time()
        
        if current_time - last_api_send >= 60:
            with lock:
                s_cin = interval_counts["car_in"]
                s_cout = interval_counts["car_out"]
                s_min = interval_counts["motor_in"]
                s_mout = interval_counts["motor_out"]
                interval_counts = {k: 0 for k in interval_counts}
                
            total_car = s_cin + s_cout
            total_motor = s_min + s_mout
            
            print(f"[API UPDATE] Mobil (In:{s_cin} Out:{s_cout}) | Motor (In:{s_min} Out:{s_mout})")
            payload = {
                "stream_id": stream_id,
                "camera_name": camera_name,
                "car_count": total_car,
                "motorcycle_count": total_motor,
                "car_in": s_cin,
                "car_out": s_cout,
                "motorcycle_in": s_min,
                "motorcycle_out": s_mout
            }
            try:
                requests.post(API_URL, json=payload, verify=False, timeout=5)
            except Exception as e:
                pass
            last_api_send = current_time
            
        if current_time - last_config_check >= 20:
            new_conf = get_ai_config()
            new_target = new_conf.get('target_camera')
            new_pos = new_conf.get('line_position', 60)
            new_dir = new_conf.get('line_direction', 'normal')
            
            if new_target != TARGET_CAMERA or new_pos != LINE_POS or new_dir != LINE_DIR:
                print("!!! Perubahan Konfigurasi Dideteksi !!!")
                print("Restarting worker...")
                cap.release()
                process.kill()
                os.execv(sys.executable, ['python'] + sys.argv)
            last_config_check = current_time
            
        time_elapsed = time.time() - loop_start
        if time_elapsed < frame_delay:
            time.sleep(frame_delay - time_elapsed)

if __name__ == "__main__":
    main()
