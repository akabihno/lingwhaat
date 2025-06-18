from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

app = FastAPI()

class WordInput(BaseModel):
    word: str

@app.post("/predict")
def predict_ipa(data: WordInput):
    word = data.word
    # TODO: Load model and make prediction
    ipa = dummy_predict(word)
    return {"ipa": ipa}

def dummy_predict(word):
    return "/ˈ" + word.lower() + "/"
