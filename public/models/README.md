# Face-API.js Models

This directory contains the machine learning models required for facial recognition functionality.

## Required Models

The following models should be present in this directory:

1. **Tiny Face Detector**
   - `tiny_face_detector_model-weights_manifest.json`
   - `tiny_face_detector_model-shard1`

2. **Face Landmark Detection**
   - `face_landmark_68_model-weights_manifest.json`
   - `face_landmark_68_model-shard1`

3. **Face Recognition**
   - `face_recognition_model-weights_manifest.json`
   - `face_recognition_model-shard1`
   - `face_recognition_model-shard2`

4. **Face Expression Recognition**
   - `face_expression_model-weights_manifest.json`
   - `face_expression_model-shard1`

## Download Models

If the models are not present, run:

```bash
node scripts/download-models.js
```

Or download manually from: https://github.com/justadudewhohacks/face-api.js/tree/master/weights

## Model Size

Total size: ~6MB
Individual models range from 300KB to 2MB each.

## Usage

These models are loaded by the FacialRecognition component to enable:
- Face detection in camera feed
- Facial landmark detection for liveness checks
- Face recognition for identity verification
- Expression detection for anti-spoofing
