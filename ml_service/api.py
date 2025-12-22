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
import train
import evaluate

app = FastAPI()

@app.post("/train-ipa/")
async def train_ipa_model_api(
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

    background_tasks.add_task(train.train_ipa_model_background, tmp_path, model_path)

    return {"status": "Training started in background", "model_path": model_path}

@app.post("/train-word/")
async def train_word_model_api(
    background_tasks: BackgroundTasks,
    file: UploadFile = File(...)
):
    if not file.filename.endswith('.csv'):
        return JSONResponse(content={"error": "Only CSV files are supported."}, status_code=400)

    base_name = os.path.splitext(os.path.basename(file.filename))[0]
    model_name = f"{base_name}_model.pt"
    model_path = os.path.join("word-models", model_name)

    with tempfile.NamedTemporaryFile(delete=False, suffix=".csv") as tmp:
        shutil.copyfileobj(file.file, tmp)
        tmp_path = tmp.name

    background_tasks.add_task(train.train_word_model_background, tmp_path, model_path)

    return {"status": "Training started in background", "model_path": model_path}

@app.get("/predict-ipa/")
def predict_ipa(word: str = Query(...), model_name: str = Query(...), file = Query(...)):
    try:
        ipa = evaluate.predict_ipa(file, word, model_name)
        return {"ipa": ipa}
    except (FileNotFoundError, KeyError, ValueError) as e:
        logging.error(f"Client error in predict_ipa: {str(e)}")
        return JSONResponse(content={"error": str(e)}, status_code=400)
    except Exception as e:
        logging.error(f"Server error in predict_ipa: {str(e)}\n{traceback.format_exc()}")
        return JSONResponse(content={"error": "Server error: " + str(e)}, status_code=500)

@app.get("/predict-word/")
def predict_word(ipa: str = Query(...), model_name: str = Query(...), file = Query(...)):
    try:
        word = evaluate.predict_word(file, ipa, model_name)
        return {"word": word}
    except (FileNotFoundError, KeyError, ValueError) as e:
        logging.error(f"Client error in predict_word: {str(e)}")
        return JSONResponse(content={"error": str(e)}, status_code=400)
    except Exception as e:
        logging.error(f"Server error in predict_word: {str(e)}\n{traceback.format_exc()}")
        return JSONResponse(content={"error": "Server error: " + str(e)}, status_code=500)