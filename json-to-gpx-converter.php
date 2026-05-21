<?php
/**
 * Plugin Name: JSON to GPX Converter (Browser JS)
 * Description: Converte file ESRI JSON in GPX sfruttando l'API pubblica su Vercel direttamente dal browser con controlli preventivi sul file.
 * Version: 1.8
 * Author: Giovanni Radicci per SalvaiciclistiRoma
 */

if (!defined('ABSPATH')) exit;

// URL della tua API pubblica ospitata su Vercel
define('FASTAPI_URL', 'https://gioradicci-esrijson-to-gpx.vercel.app/convert-json-to-gpx/');

// Registrazione dello shortcode [json_gpx_converter]
add_shortcode('json_gpx_converter', 'j2g_render_converter_form');

function j2g_render_converter_form() {
    ob_start();
    ?>
    <div class="j2g-converter-container" style="padding: 20px; border: 1px solid #ccc; border-radius: 5px; max-width: 400px; background: #fff; clear: both; box-shadow: 0 2px 5px rgba(0,0,0,0.1); font-family: sans-serif;">
        <h3 style="margin-top:0; color:#333;">Convertitore JSON in GPX</h3>
        
        <form id="j2g-gpx-form" enctype="multipart/form-data">
            <p style="margin-bottom: 15px;">
                <label for="json_file" style="display:block; margin-bottom:5px; font-weight:bold; color:#555;">Seleziona il file JSON (Max 2MB):</label>
                <input type="file" id="json_file" accept=".json" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:3px;">
            </p>
            
            <p style="margin-top: 15px;">
                <button type="submit" id="j2g_btn" class="button button-primary" style="background:#0073aa; color:#fff; padding:10px 20px; border:none; border-radius:3px; cursor:pointer; font-weight:bold; width:100%;">Converti e Scarica GPX</button>
            </p>
        </form>
        
        <!-- Contenitore per i messaggi di stato (Caricamento, Successo, Errore) -->
        <div id="j2g-status" style="margin-top:15px; padding:10px; border-radius:3px; font-weight:bold; display:none; font-size:14px;"></div>
    </div>

    <script type="text/javascript">
    document.getElementById('j2g-gpx-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('json_file');
        const statusDiv = document.getElementById('j2g-status');
        const btn = document.getElementById('j2g_btn');
        
        // 1. Verifica che l'utente abbia effettivamente selezionato un file
        if (!fileInput.files || !fileInput.files.length) return;
        
        const selectedFile = fileInput.files[0];
        
        // FUNZIONE AUSILIARIA PER MOSTRARE GLI ERRORI GRAFICI
        function showError(message) {
            statusDiv.style.display = 'block';
            statusDiv.style.background = '#fff2f2';
            statusDiv.style.color = '#dc3232';
            statusDiv.style.borderLeft = '4px solid #dc3232';
            statusDiv.innerText = message;
        }

        // 2. CONTROLLO ESTENSIONE DEL FILE (.json)
        const fileName = selectedFile.name;
        if (!fileName.toLowerCase().endsWith('.json')) {
            showError('⚠️ Errore: Puoi caricare solo file con estensione .json');
            return;
        }

        // 3. CONTROLLO DIMENSIONE DEL FILE (Max 2 Megabytes)
        const maxSizeInBytes = 2 * 1024 * 1024; // 2.097.152 byte
        if (selectedFile.size > maxSizeInBytes) {
            showError('⚠️ Errore: Il file supera il limite massimo consentito di 2 MB.');
            return;
        }
        
        // Se i controlli passano, prepariamo l'invio dei dati
        const formData = new FormData();
        formData.append('file', selectedFile);
        
        // Configura l'interfaccia grafica per lo stato di caricamento
        statusDiv.style.display = 'block';
        statusDiv.style.background = '#e7f4f9';
        statusDiv.style.color = '#0073aa';
        statusDiv.style.borderLeft = '4px solid #0073aa';
        statusDiv.innerText = 'Conversione in corso...';
        btn.disabled = true;
        btn.style.opacity = '0.6';
        
        try {
            // Chiamata AJAX asincrona diretta verso Vercel
            const response = await fetch('<?php echo esc_url(FASTAPI_URL); ?>', {
                method: 'POST',
                body: formData
            });
            
            // Se l'API restituisce un errore, cattura lo stato
            if (!response.ok) {
                const errData = await response.json().catch(() => ({ detail: 'Errore generico' }));
                throw new Error(errData.detail || 'Risposta server errata con codice ' + response.status);
            }
            
            // Riceve il flusso binario (Blob) del file GPX generato
            const blob = await response.blob();
            
            // Crea un link virtuale temporaneo per forzare il download automatico
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            
            // Rinomina il file scaricato sostituendo l'estensione .json con .gpx
            a.download = selectedFile.name.replace(/\.json$/i, '.gpx') || 'percorso.gpx';
            document.body.appendChild(a);
            a.click();
            
            // Pulisce il DOM e rilascia la memoria del browser
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            // Notifica di successo
            statusDiv.style.background = '#ecf7ed';
            statusDiv.style.color = 'green';
            statusDiv.style.borderLeft = '4px solid green';
            statusDiv.innerText = '✓ File GPX generato e scaricato con successo!';
            
        } catch (error) {
            showError('⚠️ Errore di conversione: ' + error.message);
            console.error('Dettagli errore:', error);
        } finally {
            // Ripristina lo stato del pulsante per permettere un nuovo invio
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
