# spend_trend.py
# pip install fastapi uvicorn numpy scipy flask
# to run python spend_trend.py


from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import numpy as np
from scipy import stats
import uvicorn
from typing import List, Dict, Optional
from datetime import datetime
from enum import Enum

app = FastAPI(
    title="Smart Spend Analytics Pro",
    description="Advanced spending analysis with multi-category tracking",
    version="2.0"
)

class ExpenseCategory(str, Enum):
    SUBSCRIPTIONS = "subscriptions"
    GROCERIES = "groceries"
    GAS = "gas"
    DINING = "dining"
    ENTERTAINMENT = "entertainment"
    UTILITIES = "utilities"

class SpendingRequest(BaseModel):
    expenses: Dict[ExpenseCategory, List[float]]
    budgets: Dict[ExpenseCategory, float]
    timeframe: Optional[str] = "6m"  # e.g., "6m" or "1y"

@app.post("/calc_spending", response_model=dict)
async def analyze_spending(data: SpendingRequest):
    try:
        # 1. Calculate category-wise totals
        category_totals = {
            cat: float(np.sum(np.array(amounts, dtype=float)))
            for cat, amounts in data.expenses.items()
        }
        
        # 2. Overall calculations
        total_spent = sum(category_totals.values())
        total_budget = sum(data.budgets.values())
        
        # 3. Advanced analytics by category
        analytics = {
            "by_category": {
                cat: {
                    "total": total,
                    "budget": data.budgets.get(cat, 0),
                    "percentage": (total / data.budgets[cat] * 100) if (cat in data.budgets and data.budgets[cat] != 0) else 0,
                    "avg": float(np.mean(np.array(data.expenses[cat]))) if data.expenses[cat] else 0,
                    "trend": "↑" if total > data.budgets.get(cat, 0) else "↓"
                }
                for cat, total in category_totals.items()
            },
            "total_spent": total_spent,
            "total_budget": total_budget,
            "savings": total_budget - total_spent,
            "spending_distribution": {
                cat: (total / total_spent * 100) if total_spent > 0 else 0
                for cat, total in category_totals.items()
            }
        }
        
        # 4. Generate historical trends (simulated)
        months = 6 if data.timeframe == "6m" else 12
        dates = [datetime(2023, (i % 12) + 1, 1).strftime('%b') for i in range(months)]
        analytics["historical"] = {
            "dates": dates,
            "data": {
                cat: [
                    float(total * (0.7 + 0.3 * (i / (months - 1)) + np.random.normal(0, total * 0.1)))
                    for i in range(months)
                ]
                for cat, total in category_totals.items()
            }
        }
        
        # 5. Regression analysis on historical trends (per category)
        regression_data = {}
        for cat, hist in analytics["historical"]["data"].items():
            x = np.arange(1, months + 1)
            slope, intercept, r_value, p_value, std_err = stats.linregress(x, np.array(hist))
            regression_data[cat] = {
                "slope": float(slope),
                "intercept": float(intercept),
                "r_value": float(r_value),
                "p_value": float(p_value),
                "std_err": float(std_err),
                "trend_line": (slope * x + intercept).tolist()
            }
        analytics["regression"] = regression_data
        
        # 6. Spending recommendations
        analytics["recommendations"] = generate_recommendations(analytics)
        
        return analytics
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))

def generate_recommendations(data: dict) -> List[str]:
    recs = []
    for cat, stats_data in data["by_category"].items():
        if stats_data["percentage"] > 100:
            recs.append(f"Reduce {cat.value} spending (currently {stats_data['percentage']:.0f}% of budget)")
    if data["savings"] < 0:
        recs.append(f"⚠️ You're overspending by ${abs(data['savings']):.2f}")
    else:
        recs.append(f"✅ Good job! You saved ${data['savings']:.2f}")
    return recs

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=5000)
