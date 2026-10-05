import json
import os
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix

def evaluate():
    data_path = 'training_data.json'
    if not os.path.exists(data_path):
        print("No training data found.")
        return

    with open(data_path, 'r') as f:
        data = json.load(f)

    texts = [item['text'].lower() for item in data]
    labels = [item['intent'] for item in data]

    # Split data: 80% for training, 20% for testing
    X_train, X_test, y_train, y_test = train_test_split(texts, labels, test_size=0.2, random_state=42)

    print(f"Training on {len(X_train)} examples...")
    print(f"Testing on {len(X_test)} examples...")

    # Train model
    model = make_pipeline(TfidfVectorizer(), MultinomialNB())
    model.fit(X_train, y_train)

    # Predict
    predictions = model.predict(X_test)

    # Report
    print("\n--- CLASSIFICATION REPORT ---")
    print(classification_report(y_test, predictions))

    print("\n--- FAILED PREDICTIONS ---")
    failures = 0
    for text, true_label, pred_label in zip(X_test, y_test, predictions):
        if true_label != pred_label:
            print(f"Query: '{text}'")
            print(f"  Expected: {true_label}")
            print(f"  Predicted: {pred_label}")
            print("-" * 30)
            failures += 1
    
    if failures == 0:
        print("Perfect score! No failures in the test set.")
    else:
        print(f"Total Failures: {failures}")

if __name__ == "__main__":
    evaluate()
