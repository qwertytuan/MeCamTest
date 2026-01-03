from time import time
from ultralytics import YOLO
import cv2
import numpy as np
import random

# Load the model
model = YOLO('./yolo11n.pt')


# Function to process image and detect persons
def process_image(image):
    results = model(image)
    person_detected = False
    detected_objects = []

    # Process results
    for result in results:
        boxes = result.boxes.cpu().numpy()
        for box in boxes:
            x1, y1, x2, y2 = box.xyxy[0].astype(int)
            conf = box.conf[0]
            cls = int(box.cls[0])
            class_name = model.names[cls]

            # Check if person is detected (person is typically class 0 in COCO dataset)
            if class_name.lower() == 'person':
                person_detected = True
                detected_objects.append({
                    'class': class_name,
                    'confidence': float(conf),
                    'bbox': [x1, y1, x2, y2]
                })
                # Draw green box for person
                cv2.rectangle(image, (x1, y1), (x2, y2), (0, 255, 0), 2)
            else:
                # Draw blue box for other objects
                cv2.rectangle(image, (x1, y1), (x2, y2), (255, 0, 0), 2)

            cv2.putText(image, f'{class_name} {conf:.2f}', (x1, y1 - 10),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.9, (0, 255, 0), 2)

    return image, person_detected, detected_objects

# For image
#image = cv2.imread('path/to/your/image.jpg')
#processed_image = process_image(image)
#cv2.imshow('Processed Image', processed_image)
#cv2.waitKey(0)
#cv2.destroyAllWindows()

# For video
# Motion detection sensitivity settings
threshold_value = 40  # Lower = more sensitive (range: 10-50), currently moderate
min_area = 300        # Minimum contour area for motion (range: 100-1000), currently moderate
blur_kernel = (21, 21) # Gaussian blur kernel size (smaller = more sensitive to small changes)
dilate_iterations = 2  # Number of dilation iterations (higher = larger motion areas)

timeout = 30
i = 0
cap = cv2.VideoCapture(0)
ret, baseline_frame = cap.read()
if not ret:
    print("[MotionDetect]: Failed to read initial frame")
    cap.release()
    exit()

baseline_gray = cv2.cvtColor(baseline_frame, cv2.COLOR_BGR2GRAY)
baseline_gray = cv2.GaussianBlur(baseline_gray, blur_kernel, 0)

start_time = time()
detected_frame = None

print(f"[MotionDetect]: Monitoring for motion (timeout={timeout}s)...")

while ( time() - start_time) < timeout:
    ret, frame = cap.read()
    if not ret:
        break

    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    gray = cv2.GaussianBlur(gray, blur_kernel, 0)

    # Compute difference between current frame and baseline
    delta = cv2.absdiff(baseline_gray, gray)
    thresh = cv2.threshold(delta, threshold_value, 255, cv2.THRESH_BINARY)[1]
    thresh = cv2.dilate(thresh, None, iterations=dilate_iterations)

    # Find contours
    contours, _ = cv2.findContours(thresh.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)

    motion_found = False
    for c in contours:
        if cv2.contourArea(c) < min_area:
            continue
        motion_found = True
        break

    if motion_found:
        detected_frame = frame
        print("[MotionDetect]: Motion detected!")

        # Process image with YOLO to check for person
        processed_frame, person_detected, detected_objects = process_image(detected_frame)

        if person_detected:
            print(f"[PersonDetect]: ✓ Person detected! Found {len([obj for obj in detected_objects if obj['class'].lower() == 'person'])} person(s)")
            for obj in detected_objects:
                if obj['class'].lower() == 'person':
                    print(f"  - Confidence: {obj['confidence']:.2%}, BBox: {obj['bbox']}")
            i = i + 1
            cv2.imwrite(f'Image/Processed_Image_{i}.png', processed_frame)
            print(f"[PersonDetect]: Image saved as 'Image/Processed_Image_{i}.png'")
        else:
            print("[PersonDetect]: ✗ No person detected in motion area")

        # Reset baseline after capturing motion to detect new motion
        baseline_gray = gray.copy()
        start_time = time()  # Reset timer for next detection
    else:
        # Update baseline periodically when no motion to adapt to lighting changes
        elapsed = time() - start_time
        if int(elapsed) % 5 == 0 and elapsed > 0:  # Update every 5 seconds
            baseline_gray = gray.copy()

cap.release()

