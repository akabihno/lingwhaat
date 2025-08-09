from fastapi import FastAPI, UploadFile, File
from fastapi.responses import JSONResponse
from fastapi import Query
from fastapi import BackgroundTasks
import traceback
import logging
import shutil
import os
import tempfile
import sys
import model
import train
import evaluate

app = FastAPI()

@app.post("/train/")
async def train_model_api(
    background_tasks: BackgroundTasks,
    file: UploadFile = File(...)
):
    if not file.filename.endswith('.csv'):
        return JSONResponse(content={"error": "Only CSV files are supported."}, status_code=400)

    base_name = os.path.splitext(os.path.basename(file.filename))[0]
    model_name = f"{base_name}_model.pt"
    model_path = os.path.join("models", model_name)

    with tempfile.NamedTemporaryFile(delete=False, suffix=".csv") as tmp:
        shutil.copyfileobj(file.file, tmp)
        tmp_path = tmp.name

    background_tasks.add_task(train.train_model_background, tmp_path, model_path)

    return {"status": "Training started in background", "model_path": model_path}

@app.get("/predict/")
async def predict(word: str = Query(...), model_name: str = Query(...)):
    try:
        ipa = evaluate.predict_ipa(word, model_name)
        return {"ipa": ipa}
    except Exception as e:
        return JSONResponse(content={"error": str(e)}, status_code=500)