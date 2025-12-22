#!/usr/bin/env python3
"""
Check which device PyTorch will use for training.

This script helps you understand:
- What hardware acceleration is available
- Which device will be used for training
- Expected performance relative to CPU
"""

import torch
import platform
import sys


def check_device():
    print("\n" + "="*70)
    print("PyTorch Device Configuration Check")
    print("="*70)

    # System info
    print(f"\nSystem Information:")
    print(f"  Platform: {platform.system()} {platform.machine()}")
    print(f"  Python: {sys.version.split()[0]}")
    print(f"  PyTorch: {torch.__version__}")

    # Check available devices
    print(f"\nAvailable Devices:")

    # CUDA (NVIDIA GPU)
    cuda_available = torch.cuda.is_available()
    print(f"  CUDA (NVIDIA GPU): {'✓ Available' if cuda_available else '✗ Not available'}")
    if cuda_available:
        print(f"    Device: {torch.cuda.get_device_name(0)}")
        print(f"    CUDA Version: {torch.version.cuda}")
        print(f"    Memory: {torch.cuda.get_device_properties(0).total_memory / 1e9:.1f} GB")

    # MPS (Apple Silicon GPU)
    mps_available = hasattr(torch.backends, 'mps') and torch.backends.mps.is_available()
    print(f"  MPS (Apple Silicon GPU): {'✓ Available' if mps_available else '✗ Not available'}")
    if mps_available:
        print(f"    This is an Apple Silicon Mac (M1/M2/M3)")
        print(f"    GPU acceleration will be used via Metal")

    # CPU
    print(f"  CPU: ✓ Always available (fallback)")
    print(f"    Processor: {platform.processor()}")

    # Determine which device will be used
    print(f"\n{'='*70}")
    print(f"Device Selection Priority:")
    print(f"{'='*70}")

    if cuda_available:
        device = torch.device('cuda')
        device_name = f"CUDA GPU ({torch.cuda.get_device_name(0)})"
        speedup = "10-50x faster than CPU"
        recommendation = "Excellent! NVIDIA GPU will provide maximum performance."
    elif mps_available:
        device = torch.device('mps')
        device_name = "Apple Silicon GPU (Metal)"
        speedup = "5-15x faster than CPU"
        recommendation = "Great! Apple Silicon GPU will significantly accelerate training."
    else:
        device = torch.device('cpu')
        device_name = "CPU"
        speedup = "Baseline (1x)"
        if platform.machine() == 'arm64' and platform.system() == 'Darwin':
            recommendation = "You have an Apple Silicon Mac, but MPS is not available. Update PyTorch to enable GPU acceleration:\n    pip install --upgrade torch torchvision torchaudio"
        elif platform.machine().startswith('arm') or 'ARM' in platform.processor().upper():
            recommendation = "ARM CPU detected (possibly Raspberry Pi). Training will be very slow. Consider using a more powerful machine."
        else:
            recommendation = "Using CPU. Training will work but be slower. Consider using a machine with GPU."

    print(f"\n  Selected device: {device_name}")
    print(f"  Expected speedup: {speedup}")
    print(f"\n  Recommendation:")
    print(f"  {recommendation}")

    # Performance estimates for 402k dataset
    print(f"\n{'='*70}")
    print(f"Training Time Estimates (402k samples, 50 epochs):")
    print(f"{'='*70}")

    if cuda_available:
        print(f"  Your system (CUDA GPU):  ~2-6 hours")
    elif mps_available:
        print(f"  Your system (Apple GPU): ~4-12 hours")
    else:
        if 'ARM' in platform.processor().upper() or platform.machine().startswith('arm'):
            print(f"  Your system (ARM CPU):   ~4-8 DAYS ⚠️")
            print(f"  Recommendation: Use a more powerful machine")
        else:
            print(f"  Your system (CPU):       ~1-3 days")
            print(f"  Recommendation: Enable GPU acceleration if available")

    print(f"\n  For comparison:")
    print(f"    CUDA GPU (NVIDIA):        ~2-6 hours")
    print(f"    Apple Silicon GPU (M1+):  ~4-12 hours")
    print(f"    Intel CPU (MacBook):      ~1-3 days")
    print(f"    Raspberry Pi 4:           ~4-8 days")

    print(f"\n{'='*70}")
    print(f"Quick Test:")
    print(f"{'='*70}")

    # Quick performance test
    print(f"\n  Testing tensor operations on {device_name}...")

    try:
        import time

        # Create test tensor
        size = 2000
        a = torch.randn(size, size).to(device)
        b = torch.randn(size, size).to(device)

        # Warm up
        _ = torch.matmul(a, b)

        # Time matrix multiplication
        if device.type == 'cuda':
            torch.cuda.synchronize()

        start = time.time()
        for _ in range(10):
            c = torch.matmul(a, b)
        if device.type == 'cuda':
            torch.cuda.synchronize()
        elapsed = time.time() - start

        print(f"  Matrix multiplication (2000x2000): {elapsed/10*1000:.1f}ms per operation")

        if elapsed/10 > 0.5:
            print(f"  ⚠ Slow performance detected. Check device configuration.")
        else:
            print(f"  ✓ Performance looks good!")

    except Exception as e:
        print(f"  Error during test: {e}")
        print(f"  This might indicate a problem with {device_name} support")

    print(f"\n{'='*70}\n")


if __name__ == "__main__":
    check_device()
