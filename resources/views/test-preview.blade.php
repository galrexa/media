<!-- resources/views/test-preview.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test URL Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-container {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            background-color: #f8f9fa;
        }
        .preview-img {
            max-height: 200px;
            object-fit: contain;
        }
        .favicon {
            width: 16px;
            height: 16px;
            margin-right: 5px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Test URL Preview</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="url-input" class="form-label">Masukkan URL Website</label>
                            <div class="input-group">
                                <input type="url" class="form-control" id="url-input" placeholder="https://example.com">
                                <button class="btn btn-secondary" id="preview-btn">Preview</button>
                            </div>
                            <div class="form-text">Masukkan URL lengkap termasuk http:// atau https://</div>
                        </div>

                        <div class="preview-container" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="" id="preview-img" class="img-fluid preview-img" alt="Preview">
                                </div>
                                <div class="col-md-8">
                                    <h5 id="preview-title">
                                        <img src="" id="preview-favicon" class="favicon" alt="Favicon" style="display: none;">
                                        <span id="preview-title-text"></span>
                                    </h5>
                                    <p id="preview-description" class="text-muted"></p>
                                    <p><small id="preview-url" class="text-muted"></small></p>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <strong>Debug Info:</strong>
                            <div id="debug-info">
                                <p>Status: Menunggu URL</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlInput = document.getElementById('url-input');
            const previewBtn = document.getElementById('preview-btn');
            const previewContainer = document.querySelector('.preview-container');
            const previewImg = document.getElementById('preview-img');
            const previewTitle = document.getElementById('preview-title-text'); // Ubah ke span
            const previewFavicon = document.getElementById('preview-favicon');
            const previewDescription = document.getElementById('preview-description');
            const previewUrl = document.getElementById('preview-url');
            const debugInfo = document.getElementById('debug-info');

            // Function to update debug info
            function updateDebug(message) {
                debugInfo.innerHTML += `<p>${new Date().toLocaleTimeString()}: ${message}</p>`;
            }

            previewBtn.addEventListener('click', function() {
                const url = urlInput.value.trim();
                
                if (!url) {
                    alert('Masukkan URL terlebih dahulu.');
                    return;
                }

                // Show loading state
                previewBtn.textContent = 'Loading...';
                previewBtn.disabled = true;
                updateDebug(`Meminta preview untuk URL: ${url}`);
                
                // Lakukan request ke endpoint preview
                fetch(`/preview?url=${encodeURIComponent(url)}`)
                    .then(response => {
                        updateDebug(`Respons diterima dengan status: ${response.status}`);
                        if (!response.ok) {
                            throw new Error(`Server error: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Reset button
                        previewBtn.textContent = 'Preview';
                        previewBtn.disabled = false;
                        
                        updateDebug(`Data preview diterima: ${JSON.stringify(data)}`);
                        
                        if (data.success && data.image) {
                            // Set preview image
                            previewImg.src = data.image;
                            previewImg.onerror = function() {
                                updateDebug(`Error loading image: ${data.image}`);
                                this.src = '/img/no-image.png'; // Fallback image
                            };
                            
                            // Set title
                            previewTitle.textContent = data.title || 'Tidak ada judul';
                            
                            // Set description
                            previewDescription.textContent = data.description || 'Tidak ada deskripsi';
                            
                            // Set URL
                            previewUrl.textContent = url;

                            // Set favicon (opsional)
                            if (data.favicon) {
                                previewFavicon.src = data.favicon;
                                previewFavicon.style.display = 'inline';
                                previewFavicon.onerror = function() {
                                    this.style.display = 'none'; // Sembunyikan jika gagal
                                };
                            } else {
                                previewFavicon.style.display = 'none';
                            }
                            
                            // Show preview container
                            previewContainer.style.display = 'block';
                        } else {
                            updateDebug(`Tidak ada gambar ditemukan dalam respons: ${JSON.stringify(data)}`);
                            alert('Tidak dapat menemukan gambar dari URL tersebut.');
                            previewContainer.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        // Reset button
                        previewBtn.textContent = 'Preview';
                        previewBtn.disabled = false;
                        
                        updateDebug(`Error: ${error.message}`);
                        alert(`Terjadi kesalahan saat memuat preview: ${error.message}`);
                        previewContainer.style.display = 'none';
                    });
            });

            // Auto-preview saat URL diubah dan ada enter key
            urlInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter' && this.value.trim()) {
                    previewBtn.click();
                }
            });
        });
    </script>
</body>
</html>