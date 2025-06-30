import React, { useRef, useEffect, useState, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { ST as T } from '@/components/SimpleTranslator';
import {
  Camera,
  CheckCircle,
  AlertCircle,
  RefreshCw,
  Eye,
  EyeOff,
  Loader2
} from 'lucide-react';
import * as faceapi from 'face-api.js';

interface FacialRecognitionProps {
  onVerificationComplete: (result: {
    success: boolean;
    confidence: number;
    livenessScore: number;
    capturedImage: string;
  }) => void;
  onClose: () => void;
}

const FacialRecognition: React.FC<FacialRecognitionProps> = ({
  onVerificationComplete,
  onClose
}) => {
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const { toast } = useToast();

  const [isLoading, setIsLoading] = useState(true);
  const [isModelLoaded, setIsModelLoaded] = useState(false);
  const [isCameraActive, setIsCameraActive] = useState(false);
  const [isCapturing, setIsCapturing] = useState(false);
  const [faceDetected, setFaceDetected] = useState(false);

  // SIMPLE step tracking - no complex state
  const [currentStep, setCurrentStep] = useState(0); // 0=left, 1=right, 2=up, 3=down, 4=smile
  const [stepStartTime, setStepStartTime] = useState(0);
  const [completedSteps, setCompletedSteps] = useState<boolean[]>([false, false, false, false, false]);
  const [initialPosition, setInitialPosition] = useState<{ x: number; y: number } | null>(null);
  const [isStepProcessing, setIsStepProcessing] = useState(false);

  const steps = ['left', 'right', 'up', 'down', 'smile'];

  // Legacy compatibility - derive from completedSteps (AFTER it's declared)
  const livenessChecks = {
    moveLeft: completedSteps[0] || false,
    moveRight: completedSteps[1] || false,
    moveUp: completedSteps[2] || false,
    moveDown: completedSteps[3] || false,
    smile: completedSteps[4] || false
  };
  const [verificationStep, setVerificationStep] = useState<'setup' | 'detection' | 'liveness' | 'hold_still' | 'countdown' | 'capture' | 'processing' | 'review'>('setup');
  const [countdown, setCountdown] = useState(0);
  const [capturedPhoto, setCapturedPhoto] = useState<string | null>(null);

  // Load face-api.js models
  const loadModels = useCallback(async () => {
    try {
      setIsLoading(true);
      const MODEL_URL = '/models';

      // Try to load models with timeout
      const loadPromise = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
      ]);

      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('Model loading timeout')), 30000)
      );

      await Promise.race([loadPromise, timeoutPromise]);

      setIsModelLoaded(true);
      toast({
        title: "Models Loaded",
        description: "Face recognition models loaded successfully",
      });
    } catch (error) {
      console.error('Error loading models:', error);

      // Fallback: Allow basic camera functionality without full face recognition
      setIsModelLoaded(false);
      toast({
        title: "Limited Mode",
        description: "Face recognition models unavailable. Using basic camera mode.",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  }, [toast]);

  // Start camera
  const startCamera = useCallback(async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user'
        }
      });
      
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
        streamRef.current = stream;
        setIsCameraActive(true);
        setVerificationStep('detection');
      }
    } catch (error) {
      console.error('Error accessing camera:', error);
      toast({
        title: "Camera Error",
        description: "Unable to access camera. Please check permissions.",
        variant: "destructive"
      });
    }
  }, [toast]);

  // Stop camera
  const stopCamera = useCallback(() => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach(track => track.stop());
      streamRef.current = null;
    }
    setIsCameraActive(false);
  }, []);

  // Get current movement instruction
  const getCurrentInstruction = () => {
    if (currentStep >= steps.length) return null;
    return steps[currentStep];
  };

  // Complete current step and advance
  const completeCurrentStep = useCallback(() => {
    if (isStepProcessing || currentStep >= 5) return;

    // Double-check this step hasn't already been completed
    if (completedSteps[currentStep]) {
      console.log(`Step ${currentStep + 1} already completed, skipping`);
      return;
    }

    setIsStepProcessing(true);
    console.log(`Completing step ${currentStep + 1}: ${steps[currentStep]}`);

    // Mark step as completed
    setCompletedSteps(prev => {
      const newSteps = [...prev];
      newSteps[currentStep] = true;
      console.log(`Step ${currentStep + 1} marked complete:`, newSteps);
      return newSteps;
    });

    // Wait 1.5 seconds then advance to next step (faster progression)
    setTimeout(() => {
      // Double-check we're still on the same step before advancing
      setCurrentStep(prevStep => {
        if (prevStep === currentStep && prevStep < 4) {
          console.log(`Advancing from step ${prevStep + 1} to step ${prevStep + 2}`);
          setStepStartTime(Date.now());
          setIsStepProcessing(false);
          return prevStep + 1;
        } else if (prevStep >= 4) {
          // All steps completed - move to photo capture
          console.log('All movement steps completed, moving to liveness check');
          setVerificationStep('liveness');
          setIsStepProcessing(false);
          return prevStep;
        } else {
          // Step mismatch - don't advance
          console.log(`Step mismatch: expected ${currentStep}, got ${prevStep}`);
          setIsStepProcessing(false);
          return prevStep;
        }
      });
    }, 1500); // Reduced to 1500ms for faster progression
  }, [currentStep, isStepProcessing, completedSteps]);

  // SIMPLE face detection loop
  const detectFaces = useCallback(async () => {
    if (!videoRef.current || !isModelLoaded || !isCameraActive || verificationStep !== 'detection') return;
    if (isStepProcessing || currentStep >= 5) return; // Don't process if step is being processed or completed

    const video = videoRef.current;
    const canvas = canvasRef.current;
    if (!canvas || video.videoWidth === 0) return;

    try {
      const detections = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({
          inputSize: 416,        // Higher resolution for better detection
          scoreThreshold: 0.3    // Lower threshold for easier detection (was 0.5)
        }))
        .withFaceLandmarks()
        .withFaceExpressions();

      if (detections) {
        setFaceDetected(true);

        // Get face center position
        const box = detections.detection.box;
        const currentPos = { x: box.x + box.width / 2, y: box.y + box.height / 2 };

        // Initialize position if not set
        if (!initialPosition) {
          setInitialPosition(currentPos);
          setStepStartTime(Date.now());
          return;
        }

        // Check if enough time has passed for this step (1 second - more responsive)
        if (Date.now() - stepStartTime < 1000) return;

        // Check movement or expression for current step
        const deltaX = currentPos.x - initialPosition.x;
        const deltaY = currentPos.y - initialPosition.y;
        const currentStepName = steps[currentStep];
        let stepCompleted = false;

        if (currentStepName === 'smile') {
          const expressions = detections.expressions;
          if (expressions.happy > 0.15) { // Much more sensitive smile detection
            stepCompleted = true;
            console.log('Smile detected! Happiness:', expressions.happy);
          }
        } else {
          // Ultra-minimal thresholds - just eye movement level detection
          const horizontalThreshold = 2;  // Just looking left/right with eyes
          const verticalThreshold = 1.5;  // Barely lifting eyebrows or slight nod

          switch (currentStepName) {
            case 'left':
              stepCompleted = deltaX > horizontalThreshold;
              break;
            case 'right':
              stepCompleted = deltaX < -horizontalThreshold;
              break;
            case 'up':
              stepCompleted = deltaY < -verticalThreshold;
              break;
            case 'down':
              stepCompleted = deltaY > verticalThreshold;
              break;
          }

          // Debug logging for movement detection
          if (currentStep < 4) { // Only log for movement steps, not smile
            console.log(`Step ${currentStep + 1} (${currentStepName}): deltaX=${deltaX.toFixed(1)}, deltaY=${deltaY.toFixed(1)}, threshold=${currentStepName.includes('left') || currentStepName.includes('right') ? horizontalThreshold : verticalThreshold}, completed=${stepCompleted}`);
          }

          if (stepCompleted) {
            console.log(`✅ ${currentStepName} movement detected! Delta: ${deltaX.toFixed(1)}, ${deltaY.toFixed(1)}`);
          }
        }

        // Only complete step if not already completed and not currently processing
        if (stepCompleted && !completedSteps[currentStep] && !isStepProcessing) {
          console.log(`Triggering completion for step ${currentStep + 1}: ${currentStepName}`);
          completeCurrentStep();
        }

        // Draw detection
        const displaySize = { width: video.videoWidth, height: video.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);
        const ctx = canvas.getContext('2d');
        if (ctx) {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
          const resizedDetections = faceapi.resizeResults(detections, displaySize);
          faceapi.draw.drawDetections(canvas, resizedDetections);
        }
      } else {
        setFaceDetected(false);
      }
    } catch (error) {
      console.error('Face detection error:', error);
    }
  }, [isModelLoaded, isCameraActive, verificationStep, currentStep, stepStartTime, initialPosition, completedSteps, isStepProcessing, completeCurrentStep]);



  // Calculate liveness score
  const calculateLivenessScore = useCallback(() => {
    const checks = Object.values(livenessChecks);
    const passedChecks = checks.filter(Boolean).length;
    const score = passedChecks / checks.length;

    console.log('Liveness calculation:', {
      checks: livenessChecks,
      passedChecks,
      totalChecks: checks.length,
      score
    });

    return score;
  }, [livenessChecks]);

  // Perform the actual photo capture
  const performCapture = useCallback(async () => {
    if (!videoRef.current || !canvasRef.current) return;

    setVerificationStep('processing');

    try {
      const video = videoRef.current;

      // Wait a moment for the video to stabilize after countdown
      await new Promise(resolve => setTimeout(resolve, 500));

      // Create canvas for capture
      const canvas = document.createElement('canvas');
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;

      const ctx = canvas.getContext('2d');
      if (!ctx) {
        throw new Error('Failed to get canvas context');
      }

      // Draw the current video frame to canvas
      ctx.drawImage(video, 0, 0);
      const capturedImage = canvas.toDataURL('image/jpeg', 0.8);

      // Store the captured photo for review
      setCapturedPhoto(capturedImage);

      // Perform face detection on the canvas instead of video
      const detections = await faceapi
        .detectSingleFace(canvas, new faceapi.TinyFaceDetectorOptions({
          inputSize: 416,        // Higher resolution for better detection
          scoreThreshold: 0.3    // Lower threshold for easier detection
        }))
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (detections) {
        const confidence = detections.detection.score;
        const livenessScore = calculateLivenessScore();

        console.log('Face detected with confidence:', confidence);
        console.log('Liveness score:', livenessScore);

        const isSuccessful = confidence >= 0.6 && livenessScore > 0.6; // 60% confidence minimum
        console.log('Verification decision:', {
          confidence,
          livenessScore,
          confidenceThreshold: 0.6, // Updated to 60%
          livenessThreshold: 0.6,
          isSuccessful
        });

        // Check if confidence is below 60% - force retake
        if (confidence < 0.6) {
          console.log(`Confidence too low: ${(confidence * 100).toFixed(1)}% - forcing retake`);
          toast({
            title: "Photo Quality Too Low",
            description: `Confidence score: ${(confidence * 100).toFixed(1)}%. Please retake photo for better quality (minimum 60% required).`,
            variant: "destructive"
          });

          // Automatically restart the verification process
          setTimeout(() => {
            handleRetakePhoto();
          }, 3000); // Give user time to read the message

          return; // Don't proceed to review step
        }

        // Show review step only if confidence is adequate
        setVerificationStep('review');
        setIsCapturing(false);

        // Store the verification result for later submission
        window.verificationResult = {
          success: isSuccessful,
          confidence,
          livenessScore,
          capturedImage
        };
      } else {
        // Try alternative detection method
        console.log('First detection failed, trying alternative method...');

        const alternativeDetections = await faceapi
          .detectAllFaces(canvas, new faceapi.TinyFaceDetectorOptions({
            inputSize: 416,        // Higher resolution for better detection
            scoreThreshold: 0.2    // Even lower threshold for alternative method
          }))
          .withFaceLandmarks()
          .withFaceDescriptors();

        if (alternativeDetections.length > 0) {
          const bestDetection = alternativeDetections[0];
          const confidence = bestDetection.detection.score;
          const livenessScore = calculateLivenessScore();

          console.log('Alternative detection succeeded with confidence:', confidence);

          const isSuccessful = confidence >= 0.6; // Same 60% threshold for consistency
          console.log('Alternative verification decision:', {
            confidence,
            livenessScore,
            confidenceThreshold: 0.6, // Updated to match main threshold
            isSuccessful
          });

          // Check if confidence is below 60% - force retake
          if (confidence < 0.6) {
            console.log(`Alternative detection - Confidence too low: ${(confidence * 100).toFixed(1)}% - forcing retake`);
            toast({
              title: "Photo Quality Too Low",
              description: `Confidence score: ${(confidence * 100).toFixed(1)}%. Please retake photo for better quality (minimum 60% required).`,
              variant: "destructive"
            });

            // Automatically restart the verification process
            setTimeout(() => {
              handleRetakePhoto();
            }, 3000); // Give user time to read the message

            return; // Don't proceed to review step
          }

          // Show review step only if confidence is adequate
          setVerificationStep('review');
          setIsCapturing(false);

          // Store the verification result for later submission
          window.verificationResult = {
            success: isSuccessful,
            confidence,
            livenessScore,
            capturedImage
          };
        } else {
          throw new Error('No face detected in final capture');
        }
      }
    } catch (error) {
      console.error('Capture error:', error);
      toast({
        title: "Capture Error",
        description: "Failed to capture and verify face. Please try again.",
        variant: "destructive"
      });

      // Reset to detection step to allow retry
      setVerificationStep('detection');
      setIsCapturing(false);
      setCapturedPhoto(null);
      // Reset simple state
      setCurrentStep(0);
      setCompletedSteps([false, false, false, false, false]);
      setInitialPosition(null);
      setStepStartTime(Date.now());
      setIsStepProcessing(false);
    }
  }, [toast, calculateLivenessScore]);

  // Start countdown and automatic capture
  const startCountdownAndCapture = useCallback(async () => {
    if (!videoRef.current || !canvasRef.current) return;

    setIsCapturing(true);

    // Countdown from 3
    for (let i = 3; i > 0; i--) {
      setCountdown(i);
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
    setCountdown(0);

    // Capture the photo
    await performCapture();
  }, [performCapture]);

  // Handle photo approval
  const handleApprovePhoto = useCallback(() => {
    if (window.verificationResult) {
      // Stop camera and submit result
      stopCamera();
      onVerificationComplete(window.verificationResult);
    }
  }, [onVerificationComplete, stopCamera]);

  // Handle photo retake
  const handleRetakePhoto = useCallback(async () => {
    console.log('Retaking photo - resetting all states');

    // Stop current camera first
    stopCamera();

    // Reset all states
    setCapturedPhoto(null);
    setVerificationStep('setup'); // Go back to setup to show start camera button
    setIsCapturing(false);
    setCurrentStep(0);
    setCompletedSteps([false, false, false, false, false]);
    setInitialPosition(null);
    setStepStartTime(Date.now());
    setIsStepProcessing(false);
    setFaceDetected(false);

    console.log('All states reset, user can now start camera again');
  }, [stopCamera]);



  // Handle transition from liveness to countdown
  useEffect(() => {
    if (verificationStep === 'liveness') {
      setTimeout(() => {
        setVerificationStep('hold_still');
        setTimeout(() => {
          setVerificationStep('countdown');
          startCountdownAndCapture();
        }, 2000);
      }, 1500);
    }
  }, [verificationStep, startCountdownAndCapture]);

  // Face detection interval
  useEffect(() => {
    if (isCameraActive && isModelLoaded) {
      const interval = setInterval(detectFaces, 100);
      return () => clearInterval(interval);
    }
  }, [detectFaces, isCameraActive, isModelLoaded]);

  // Remove auto-pass - make it require real actions



  // Load models on mount
  useEffect(() => {
    loadModels();
    return () => stopCamera();
  }, [loadModels, stopCamera]);

  return (
    <Card className="bg-gray-800 border-gray-700 max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle className="text-white flex items-center gap-2">
          <Camera className="h-5 w-5 text-blue-400" />
          <T k="facial_recognition_verification" fallback="Facial Recognition Verification" />
          <Badge className="bg-blue-500/20 text-blue-400">
            {verificationStep === 'setup' && 'Setup'}
            {verificationStep === 'detection' && 'Face Detection'}
            {verificationStep === 'liveness' && 'Liveness Check'}
            {verificationStep === 'hold_still' && 'Hold Still'}
            {verificationStep === 'countdown' && 'Get Ready'}
            {verificationStep === 'capture' && 'Capturing'}
            {verificationStep === 'processing' && 'Processing'}
            {verificationStep === 'review' && 'Review Photo'}
          </Badge>
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Camera View / Photo Review */}
        <div className="relative bg-black rounded-lg overflow-hidden">
          {verificationStep === 'review' && capturedPhoto ? (
            // Show captured photo for review with confidence score overlay
            <div className="relative">
              <img
                src={capturedPhoto}
                alt="Captured verification photo"
                className="w-full h-80 object-cover"
              />
              {/* Confidence Score Overlay */}
              {window.verificationResult && (
                <div className="absolute top-4 right-4 space-y-2">
                  <div className={`px-3 py-2 rounded-lg text-sm font-semibold ${
                    window.verificationResult.confidence >= 0.6
                      ? 'bg-green-500/20 text-green-400 border border-green-500/30'
                      : 'bg-red-500/20 text-red-400 border border-red-500/30'
                  }`}>
                    <div className="flex items-center gap-2">
                      {window.verificationResult.confidence >= 0.6 ? (
                        <CheckCircle className="h-4 w-4" />
                      ) : (
                        <AlertCircle className="h-4 w-4" />
                      )}
                      <span>Confidence: {(window.verificationResult.confidence * 100).toFixed(1)}%</span>
                    </div>
                  </div>
                  <div className="bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-2 rounded-lg text-sm font-semibold">
                    <div className="flex items-center gap-2">
                      <CheckCircle className="h-4 w-4" />
                      <span>Liveness: {(window.verificationResult.livenessScore * 100).toFixed(1)}%</span>
                    </div>
                  </div>
                </div>
              )}
            </div>
          ) : (
            // Show live camera feed
            <>
              <video
                ref={videoRef}
                autoPlay
                muted
                playsInline
                className="w-full h-80 object-cover"
                style={{ transform: 'scaleX(-1)' }} // Mirror effect
              />
              <canvas
                ref={canvasRef}
                className="absolute top-0 left-0 w-full h-full"
                style={{ transform: 'scaleX(-1)' }}
              />
            </>
          )}
          
          {/* Countdown overlay */}
          {countdown > 0 && (
            <div className="absolute inset-0 flex items-center justify-center bg-black/50">
              <div className="text-6xl font-bold text-white">{countdown}</div>
            </div>
          )}

          {/* Movement instruction overlay */}
          {verificationStep === 'detection' && (() => {
            const currentInstruction = getCurrentInstruction();
            if (currentInstruction) {
              const instructions = {
                left: { text: 'MOVE LEFT', icon: '←', color: 'bg-blue-600' },
                right: { text: 'MOVE RIGHT', icon: '→', color: 'bg-green-600' },
                up: { text: 'MOVE UP', icon: '↑', color: 'bg-purple-600' },
                down: { text: 'MOVE DOWN', icon: '↓', color: 'bg-orange-600' },
                smile: { text: 'SMILE BIG!', icon: '😊', color: 'bg-yellow-600' }
              };
              const instruction = instructions[currentInstruction];
              return (
                <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2">
                  <div className={`${instruction.color} text-white px-6 py-3 rounded-full flex items-center gap-3 shadow-lg`}>
                    <span className="text-2xl">{instruction.icon}</span>
                    <span className="text-xl font-bold">{instruction.text}</span>
                    <span className="text-2xl">{instruction.icon}</span>
                  </div>
                </div>
              );
            }
            return null;
          })()}
          
          {/* Status indicators - only show during active detection */}
          {verificationStep !== 'review' && (
            <>
              <div className="absolute top-4 left-4 space-y-2">
                <div className={`flex items-center gap-2 px-3 py-1 rounded-full text-sm ${
                  faceDetected ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'
                }`}>
                  {faceDetected ? <CheckCircle className="h-3 w-3" /> : <AlertCircle className="h-3 w-3" />}
                  {faceDetected ? 'Face Detected' : 'No Face Detected'}
                </div>
                {/* Movement progress display */}
                {faceDetected && verificationStep === 'detection' && (
                  <div className="space-y-1">
                    <div className="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs">
                      Step: {currentStep + 1}/5
                    </div>
                  </div>
                )}
              </div>

              {/* Movement checks */}
              <div className="absolute top-4 right-4 space-y-1">
                {steps.map((step, index) => (
                  <div key={step} className={`flex items-center gap-2 px-2 py-1 rounded text-xs ${
                    completedSteps[index] ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'
                  }`}>
                    {completedSteps[index] ? <CheckCircle className="h-3 w-3" /> : <Eye className="h-3 w-3" />}
                    {step === 'left' && '← Left'}
                    {step === 'right' && '→ Right'}
                    {step === 'up' && '↑ Up'}
                    {step === 'down' && '↓ Down'}
                    {step === 'smile' && '😊 Smile'}
                  </div>
                ))}
              </div>
            </>
          )}
        </div>

        {/* Instructions */}
        <div className="text-center space-y-2">
          {verificationStep === 'setup' && (
            <p className="text-gray-400">
              <T k="facial_recognition_setup" fallback="Setting up facial recognition..." />
            </p>
          )}
          {verificationStep === 'detection' && (
            <div className="space-y-4">
              {(() => {
                const currentInstruction = getCurrentInstruction();
                const allStepsCompleted = Object.values(livenessChecks).every(check => check);

                if (currentInstruction && !allStepsCompleted) {
                  const instructions = {
                    left: {
                      text: 'MOVE YOUR HEAD LEFT',
                      icon: '←',
                      bg: 'bg-blue-600',
                      description: 'Turn your head to your left side'
                    },
                    right: {
                      text: 'MOVE YOUR HEAD RIGHT',
                      icon: '→',
                      bg: 'bg-green-600',
                      description: 'Turn your head to your right side'
                    },
                    up: {
                      text: 'MOVE YOUR HEAD UP',
                      icon: '↑',
                      bg: 'bg-purple-600',
                      description: 'Tilt your head upward'
                    },
                    down: {
                      text: 'MOVE YOUR HEAD DOWN',
                      icon: '↓',
                      bg: 'bg-orange-600',
                      description: 'Tilt your head downward'
                    },
                    smile: {
                      text: 'SMILE BIG!',
                      icon: '😊',
                      bg: 'bg-yellow-600',
                      description: 'Give us your biggest, happiest smile!'
                    }
                  };
                  const instruction = instructions[currentInstruction];
                  return (
                    <div className="space-y-3">
                      <div className={`${instruction.bg} text-white px-6 py-4 rounded-lg text-center`}>
                        <div className="flex items-center justify-center gap-4 mb-2">
                          <span className="text-4xl">{instruction.icon}</span>
                          <span className="text-2xl font-bold">{instruction.text}</span>
                          <span className="text-4xl">{instruction.icon}</span>
                        </div>
                        <p className="text-lg opacity-90">{instruction.description}</p>
                      </div>
                      <div className="text-center">
                        <p className="text-gray-300 text-lg">
                          Step {currentStep + 1} of 5
                        </p>
                      </div>
                    </div>
                  );
                }

                // Debug logging for completion state
                console.log('Completion check:', {
                  currentInstruction,
                  allStepsCompleted,
                  livenessChecks
                });

                return (
                  <div className="bg-green-600 text-white px-6 py-4 rounded-lg text-center">
                    <p className="text-2xl font-bold">✅ ALL STEPS COMPLETED!</p>
                    <p className="text-lg opacity-90">Great job! Preparing to take photo...</p>
                  </div>
                );
              })()}
            </div>
          )}
          {verificationStep === 'liveness' && (
            <p className="text-green-400">
              <T k="liveness_checks_complete" fallback="Great! All liveness checks passed. Preparing to capture..." />
            </p>
          )}
          {verificationStep === 'hold_still' && (
            <p className="text-blue-400 text-lg font-semibold">
              <T k="hold_still" fallback="Hold Still! Photo will be taken automatically." />
            </p>
          )}
          {verificationStep === 'countdown' && countdown > 0 && (
            <p className="text-yellow-400 text-lg font-semibold">
              <T k="get_ready" fallback="Get Ready!" />
            </p>
          )}
          {verificationStep === 'capture' && (
            <p className="text-blue-400">
              <T k="capturing_photo" fallback="Capturing photo..." />
            </p>
          )}
          {verificationStep === 'processing' && (
            <p className="text-yellow-400">
              <T k="processing_verification" fallback="Processing verification..." />
            </p>
          )}
          {verificationStep === 'review' && (
            <div className="space-y-2">
              <p className="text-green-400 text-lg font-semibold">
                <T k="photo_captured" fallback="Photo Captured!" />
              </p>
              <p className="text-gray-400">
                <T k="review_photo_instruction" fallback="Please review your photo. Does it look good?" />
              </p>
              {window.verificationResult && window.verificationResult.confidence >= 0.6 && (
                <p className="text-green-400 text-sm">
                  ✅ Quality Score: {(window.verificationResult.confidence * 100).toFixed(1)}% (Excellent!)
                </p>
              )}
              {window.verificationResult && window.verificationResult.confidence < 0.6 && (
                <p className="text-red-400 text-sm">
                  ⚠️ Quality Score: {(window.verificationResult.confidence * 100).toFixed(1)}% (Below 60% - Retake Required)
                </p>
              )}
            </div>
          )}
        </div>

        {/* Action buttons */}
        <div className="flex gap-2 justify-center">
          {!isCameraActive && !isLoading && isModelLoaded && verificationStep === 'setup' && (
            <Button
              onClick={startCamera}
              className="bg-blue-600 hover:bg-blue-700 text-white"
            >
              <Camera className="h-4 w-4 mr-2" />
              <T k="start_camera" fallback="Start Camera" />
            </Button>
          )}

          {verificationStep === 'review' && (
            <>
              <Button
                onClick={handleApprovePhoto}
                className="bg-green-600 hover:bg-green-700 text-white"
              >
                <CheckCircle className="h-4 w-4 mr-2" />
                <T k="approve_photo" fallback="Looks Good!" />
              </Button>
              <Button
                onClick={handleRetakePhoto}
                variant="outline"
                className="border-yellow-600 text-yellow-400 hover:bg-yellow-600/10"
              >
                <RefreshCw className="h-4 w-4 mr-2" />
                <T k="retake_photo" fallback="Retake Photo" />
              </Button>
            </>
          )}

          {verificationStep !== 'review' && (
            <Button
              onClick={onClose}
              variant="outline"
              className="border-gray-600 text-gray-400"
            >
              <T k="cancel" fallback="Cancel" />
            </Button>
          )}
        </div>

        {/* Loading state */}
        {isLoading && (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="h-8 w-8 animate-spin text-blue-400" />
            <span className="ml-2 text-gray-400">
              <T k="loading_models" fallback="Loading face recognition models..." />
            </span>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default FacialRecognition;
