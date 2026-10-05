import os
import re
import json
import joblib
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline

class NLPEngine:
    def __init__(self, model_path, data_path, synonym_path):
        self.model_path = model_path
        self.data_path = data_path
        self.synonym_path = synonym_path
        self.synonyms = self._load_synonyms()
        self.model = self._get_model()

    def _load_synonyms(self):
        if not os.path.exists(self.synonym_path):
            return {}
        with open(self.synonym_path, 'r') as f:
            return json.load(f)

    def _get_model(self):
        model_exists = os.path.exists(self.model_path)
        data_exists = os.path.exists(self.data_path)
        
        should_train = not model_exists
        if model_exists and data_exists:
            # If training data is newer than the saved model, retrain
            if os.path.getmtime(self.data_path) > os.path.getmtime(self.model_path):
                print("Training data updated. Retraining model...")
                should_train = True

        if not should_train:
            return joblib.load(self.model_path)

        if not data_exists:
            print("WARNING: Training data not found. AI model will not be available.")
            return None

        with open(self.data_path, 'r') as f:
            training_data = json.load(f)

        texts = [item['text'].lower() for item in training_data]
        labels = [item['intent'] for item in training_data]

        model = make_pipeline(TfidfVectorizer(), MultinomialNB())
        model.fit(texts, labels)
        joblib.dump(model, self.model_path)
        print("AI model trained and saved.")
        return model

    def normalize_query(self, query):
        query = query.lower()
        for standard, variations in self.synonyms.items():
            for var in variations:
                query = re.sub(r'\b' + re.escape(var) + r'\b', standard, query)
        return query

    def predict_intent(self, query):
        if not self.model:
            return 'unknown'
        normalized_query = self.normalize_query(query)
        return self.model.predict([normalized_query])[0]
