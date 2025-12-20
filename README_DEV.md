# Model training with GPU acceleration outside docker

## Mac:

## On your Mac (not in Docker)
## Install Homebrew if needed
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

## Install Python
brew install python@3.11

## Install PyTorch with MPS support
pip3 install torch torchvision torchaudio pandas scikit-learn fastapi uvicorn

Step 2: Copy ml_service to your Mac and run training:

## On your Mac
cd /path/to/lingwhaat/ml_service

## Check that GPU is now available
python3 check_device.py\
Should show: "MPS (Apple Silicon GPU): ✓ Available"

## Run training with GPU acceleration
python3 -c "from train import train_ipa_model; train_ipa_model('data/russian.csv', 'models/russian_model.pt')"

## Expected output:
Using device: Apple Silicon GPU (Metal)
...
Epoch 1/500 (15.2s)

## AWS instance with GPU and Deep Learning OSS Nvidia Driver

## Activate Python venv
source /opt/lingwhaat/ml_service/venv/bin/activate

## Uninstall CPU-only version
pip uninstall -y torch torchvision torchaudio

## Install CUDA-enabled version (for CUDA 12.x/13.x)
pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cu124

## Verify GPU is now detected
python check_device.py

You should now see:
CUDA (NVIDIA GPU): ✓ Available
Device: NVIDIA L4
CUDA Version: 12.4
Memory: 23.0 GB

Expected Performance Improvement

With NVIDIA L4 GPU:

| Setup          | Time per Epoch | 50 Epochs | Speedup           |
|----------------|----------------|-----------|-------------------|
| Before (CPU)   | 2-4 hours      | 4-8 days  | 1x                |
| After (L4 GPU) | 3-8 minutes    | 2-7 hours | 20-50x faster! 🚀 |

Start Training with GPU

Once GPU is detected:

## In screen session (so it continues if you disconnect)
screen -S gpu_training

## Activate venv
cd /opt/lingwhaat/ml_service
source venv/bin/activate

## Verify GPU one more time
python check_device.py

## Start training - will automatically use GPU
python -c "from train import train_ipa_model; train_ipa_model('data/russian.csv', 'models/russian_model.pt')"

## You should see:
Using device: CUDA GPU (NVIDIA L4)
Estimated model size: 26.12 MB
============================================================
Epoch 1/500 (8.5s)

## Detach: Ctrl+A then D
## Reattach later: screen -r gpu_training

Monitor GPU Usage

While training runs:

## Watch GPU utilization in real-time
watch -n 1 nvidia-smi

## You should see:
GPU-Util: 70-95%
Memory-Usage: 2-5 GiB / 23 GiB