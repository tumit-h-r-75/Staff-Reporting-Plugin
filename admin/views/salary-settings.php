<div class="wrap">
    <h1>Salary Settings</h1>
    
    <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Global Settings</h2>
            
            <form method="post" action="">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Default Monthly Salary (CAD)</label>
                    <input type="number" step="0.01" value="3500.00" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem;" readonly>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Working Days Per Month</label>
                    <input type="number" value="22" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem;" readonly>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Daily Rate (Calculated)</label>
                    <input type="text" value="CAD 159.09" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem; background: #f7fafc;" readonly>
                </div>
            </form>
        </div>
        
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">How It Works</h2>
            
            <div style="background: #ebf8ff; padding: 20px; border-radius: 8px; border-left: 4px solid #4299e1;">
                <h3 style="margin-top: 0; color: #2b6cb0; font-size: 1.1rem;">Salary Calculation</h3>
                <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #2d3748;">
                    <li><strong>Daily Rate</strong> = Monthly Salary ÷ Working Days</li>
                    <li><strong>Missing Days</strong> = Working Days - Reports Submitted</li>
                    <li><strong>Deduction</strong> = Missing Days × Daily Rate</li>
                    <li><strong>Net Salary</strong> = Monthly Salary - Deduction</li>
                </ul>
            </div>
            
            <div style="margin-top: 20px; background: #c6f6d5; padding: 20px; border-radius: 8px; border-left: 4px solid #48bb78;">
                <h3 style="margin-top: 0; color: #276749; font-size: 1.1rem;">Example</h3>
                <p style="margin: 0; color: #2d3748; line-height: 1.6;">
                    <strong>Monthly:</strong> CAD 3,500<br>
                    <strong>Days:</strong> 22<br>
                    <strong>Daily:</strong> CAD 159.09<br>
                    <strong>Submitted:</strong> 18 days<br>
                    <strong>Missing:</strong> 4 days<br>
                    <strong>Deduction:</strong> CAD 636.36<br>
                    <strong>Net:</strong> CAD 2,863.64
                </p>
            </div>
        </div>
    </div>
</div>
