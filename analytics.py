# analytics.py

#If your historical data is recorded monthly, then "Period 1" is the forecast for the next month,
#"Period 2" is the forecast for the month after that, and so on.
#If your data is annual, then each period would represent one year.

import os
import numpy as np
import pandas as pd
from statsmodels.tsa.arima.model import ARIMA
from sklearn.ensemble import IsolationForest
import openai
from dotenv import load_dotenv

# Load environment variables from the .env file at the specified path.
dotenv_path = r"C:\xampp\htdocs\test2\Smart-Spend-main\.env"
load_dotenv(dotenv_path=dotenv_path)

# Retrieve and set the OpenAI API key from the environment.
openai_api_key = os.getenv("OPENAI_API_KEY")
if not openai_api_key:
    raise Exception("OPENAI_API_KEY is not set in the .env file.")
openai.api_key = openai_api_key

def train_forecasting_model(historical_data, order=(1, 1, 1)):
    """
    Trains an ARIMA model on historical spending data.
    
    :param historical_data: List or array of historical spending data (numeric values).
    :param order: Tuple for the ARIMA model order (p, d, q); default is (1, 1, 1).
    :return: Fitted ARIMA model.
    """
    series = pd.Series(historical_data)
    model = ARIMA(series, order=order)
    model_fit = model.fit()
    return model_fit

def forecast_spending(model_fit, forecast_period=3):
    """
    Forecasts future spending using the trained ARIMA model.
    
    :param model_fit: A fitted ARIMA model.
    :param forecast_period: Number of future time periods to forecast.
    :return: List of forecasted spending values.
    """
    forecast = model_fit.forecast(steps=forecast_period)
    return forecast.tolist()

def train_anomaly_detector(historical_data, contamination=0.1):
    """
    Trains an IsolationForest anomaly detector on historical spending data.
    
    :param historical_data: List or array of historical spending data.
    :param contamination: Proportion of data expected to be anomalies (default 0.1).
    :return: Trained IsolationForest model.
    """
    X = np.array(historical_data).reshape(-1, 1)
    clf = IsolationForest(contamination=contamination, random_state=42)
    clf.fit(X)
    return clf

def detect_anomalies(anomaly_model, historical_data):
    """
    Uses the trained IsolationForest model to detect anomalies in historical data.
    
    :param anomaly_model: The trained IsolationForest model.
    :param historical_data: List of historical spending values.
    :return: List of values flagged as anomalies.
    """
    X = np.array(historical_data).reshape(-1, 1)
    preds = anomaly_model.predict(X)  # -1 indicates anomaly, 1 indicates normal
    anomalies = [val for val, pred in zip(historical_data, preds) if pred == -1]
    return anomalies

def generate_recommendations(prompt, max_tokens=100):
    """
    Generates natural language recommendations using OpenAI's ChatCompletion API.
    
    :param prompt: String prompt to send to the API.
    :param max_tokens: Maximum number of tokens in the generated response.
    :return: Generated recommendation text.
    """
    response = openai.ChatCompletion.create(
        model="gpt-3.5-turbo",
        messages=[
            {"role": "system", "content": "You are a financial assistant providing recommendations based on spending trends."},
            {"role": "user", "content": prompt}
        ],
        max_tokens=max_tokens,
        temperature=0.7
    )
    return response.choices[0].message['content'].strip()


# If run as a standalone script, demonstrate the analytics functions with sample data.
if __name__ == "__main__":
    # Example historical spending data
    historical_data = [100, 120, 130, 125, 140, 150, 160]
    print("Historical Data:", historical_data)
    
    # Forecasting: Train model and forecast next 3 periods
    try:
        model_fit = train_forecasting_model(historical_data)
        forecast = forecast_spending(model_fit, forecast_period=3)
        print("Forecast for next 3 periods:", forecast)
    except Exception as e:
        print("Forecast error:", str(e))
    
    # Anomaly Detection: Train detector and find anomalies
    try:
        anomaly_model = train_anomaly_detector(historical_data)
        anomalies = detect_anomalies(anomaly_model, historical_data)
        print("Detected anomalies:", anomalies)
    except Exception as e:
        print("Anomaly detection error:", str(e))
    
    # Generate Recommendations: Use OpenAI API to create suggestions
    try:
        prompt = (
            f"Based on historical spending data {historical_data} and the forecast {forecast}, "
            "what recommendations would you suggest for optimizing spending and avoiding overspending?"
        )
        recommendation = generate_recommendations(prompt)
        print("Recommendation:", recommendation)
    except Exception as e:
        print("Recommendation generation error:", str(e))
