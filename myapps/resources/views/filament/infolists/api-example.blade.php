<div style="background: #1f2937; color: #10b981; padding: 1.5rem; border-radius: 8px; font-family: 'Courier New', monospace; font-size: 0.875rem; margin-top: 1rem;">
    <div style="color: #9ca3af; margin-bottom: 0.5rem;">
        # Contoh Request SSOT API
    </div>
    <div>
        <span style="color: #60a5fa;">GET</span> 
        <span style="color: #fbbf24;">{{ url('/api/v1/staf/' . auth()->user()->no_kp) }}</span>
    </div>
    
    <div style="margin-top: 1.5rem; color: #9ca3af; margin-bottom: 0.5rem;">
        # Response (JSON)
    </div>
    <div style="color: #a78bfa;">
        {<br>
        &nbsp;&nbsp;"no_kp": "{{ auth()->user()->no_kp }}",<br>
        &nbsp;&nbsp;"nama": "{{ auth()->user()->nama }}",<br>
        &nbsp;&nbsp;"emel": "{{ auth()->user()->emel }}",<br>
        &nbsp;&nbsp;"jawatan": "{{ auth()->user()->jawatan->nama_jawatan ?? 'N/A' }}",<br>
        &nbsp;&nbsp;"gred": "{{ auth()->user()->gred->nama_gred ?? 'N/A' }}",<br>
        &nbsp;&nbsp;"bahagian": "{{ auth()->user()->bahagian->nama_bahagian ?? 'N/A' }}"<br>
        }
    </div>
</div>

