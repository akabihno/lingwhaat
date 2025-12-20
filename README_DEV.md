# Model training with GPU acceleration outside docker

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
Epoch 1/500 (15.2s)  ← Much faster!