<div class="wrap">
    <h1>Working Days & Holidays</h1>
    
    <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Working Days</h2>
            
            <form method="post" action="">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Default Working Days</label>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" checked> Sunday
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" checked> Monday
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" checked> Tuesday
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" checked> Wednesday
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" checked> Thursday
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" checked> Friday
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox"> Saturday
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <div style="flex: 1; min-width: 300px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Add Holiday</h2>
            
            <form method="post" action="">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Holiday Date</label>
                    <input type="date" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d3748;">Holiday Name</label>
                    <input type="text" placeholder="e.g., Christmas Day" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 1rem;">
                </div>
                
                <button type="button" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer;">Add Holiday</button>
            </form>
        </div>
    </div>
    
    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
        <h2 style="margin-top: 0; margin-bottom: 20px; font-size: 1.3rem; color: #2d3748;">Upcoming Holidays</h2>
        
        <p style="color: #4a5568;">No holidays scheduled yet. Add holidays using the form above.</p>
    </div>
</div>
