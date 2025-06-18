from fastapi import FastAPI, UploadFile, File
from fastapi.responses import JSONResponse
from fastapi import Query
import traceback
import logging
import shutil
import os
import tempfile
import sys
import model

app = FastAPI()

@app.post("/train/")
async def train_model_api(file: UploadFile = File(...)):
    if not file.filename.endswith('.csv'):
        return JSONResponse(content={"error": "Only CSV files are supported."}, status_code=400)

    with tempfile.NamedTemporaryFile(delete=False, suffix=".csv") as tmp:
        shutil.copyfileobj(file.file, tmp)
        tmp_path = tmp.name

    try:
        model.train_model(tmp_path)
        return {"status": "Training completed successfully"}
    except Exception as e:
        logging.error("Training failed", exc_info=True)
        return JSONResponse(content={
            "error": str(e),
            "trace": traceback.format_exc()
        }, status_code=500)
    finally:
        os.remove(tmp_path)

@app.get("/predict/")
def predict(word: str = Query(...), model_name: str = Query(...)):
    try:
        ipa = model.predict_ipa(word, model_name)
        return {"ipa": ipa}
    except Exception as e:
        return JSONResponse(content={"error": str(e)}, status_code=500)