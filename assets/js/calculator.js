document.addEventListener('DOMContentLoaded', function() {
    if (typeof mpx_ajax === 'undefined') {
        mpx_ajax = { ajax_url: window.location.protocol + '//' + window.location.host + '/wp-admin/admin-ajax.php', nonce: '', pdf_nonce: '', qr_nonce: '' };
    }

    const originSelect = document.getElementById('mpx-origin');
    const modeField = document.getElementById('mpx-mode-field');
    const modeSelect = document.getElementById('mpx-mode');
    const dimensionsToggle = document.getElementById('mpx-dimensions-toggle');
    const dimensionsDiv = document.getElementById('mpx-dimensions');
    const form = document.getElementById('mpx-calculator-form');
    const resultDiv = document.getElementById('mpx-result');
    const calculateBtn = document.getElementById('mpx-calculate-btn');
    const categorySelect = document.getElementById('mpx-category');
    const quantityField = document.getElementById('mpx-quantity-field');
    const quantityInput = document.getElementById('mpx-quantity');
    const resetBtn = document.getElementById('mpx-reset-btn');

    // Create a container for the dynamic result card
    const resultCardContainer = document.createElement('div');
    resultCardContainer.id = 'mpx-result-card-container';
    resultDiv.prepend(resultCardContainer);

    const resultActions = document.getElementById('mpx-result-actions');
    const downloadPdfBtn = document.getElementById('mpx-download-pdf-btn');
    const getQrBtn = document.getElementById('mpx-get-qr-btn');
    const qrCodeContainer = document.getElementById('mpx-qr-code-container');
    const exportFeeNote = document.querySelector('.mpx-export-fee-note');

    let currentQuoteData = null;

    // Origin change handler
    originSelect.addEventListener('change', function() {
        const origin = this.value;
        modeSelect.innerHTML = '<option value="">Select Mode</option>';

        if (origin === 'china') {
            modeField.style.display = 'block';
            modeSelect.required = true;
            modeSelect.innerHTML += '<option value="china_express_air">Express Air (10–14 Days)</option>';
            modeSelect.innerHTML += '<option value="china_normal">Normal Air (15–30 Days)</option>';
            modeSelect.innerHTML += '<option value="china_normal_sea">Normal Sea (50–60 Days)</option>';
            modeSelect.innerHTML += '<option value="china_express_sea">Express Sea (45 Days)</option>';
        } else if (origin === 'dubai') {
            modeField.style.display = 'block';
            modeSelect.required = true;
            modeSelect.innerHTML += '<option value="dubai_express">Express Air (7–14 Days)</option>';
            modeSelect.innerHTML += '<option value="dubai_standard_sea">Standard Sea (30–45 Days)</option>';
        } else if (origin === 'usa') {
            modeField.style.display = 'block';
            modeSelect.required = true;
            modeSelect.innerHTML += '<option value="usa_express">Express Air (7–14 Days)</option>';
        } else if (origin === 'uk') {
            modeField.style.display = 'block';
            modeSelect.required = true;
            modeSelect.innerHTML += '<option value="uk_duty_incl">Duty-Incl Air (7–10 Days)</option>';
        } else if (origin === 'india') {
            modeField.style.display = 'block';
            modeSelect.required = true;
            modeSelect.innerHTML += '<option value="india_standard">Standard Air (7–14 Days)</option>';
        } else {
            modeField.style.display = 'none';
            modeSelect.required = false;
        }
    });

    // Category change handler
    categorySelect.addEventListener('change', function() {
        const isPhones = this.value === 'phones';
        quantityField.style.display = isPhones ? 'block' : 'none';
        quantityInput.required = isPhones;
        if (isPhones) {
            quantityInput.value = 1; // Default to 1
        }
    });

    // Dimensions toggle
    dimensionsToggle.addEventListener('change', function() {
        dimensionsDiv.style.display = this.checked ? 'block' : 'none';
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            resultCardContainer.innerHTML = '<div class="mpx-error">Please fill all required fields.</div>';
            resultDiv.style.display = 'block';
            return;
        }
        
        calculateBtn.disabled = true;
        calculateBtn.classList.add('mpx-loading');
        resultDiv.style.display = 'block';
        resultCardContainer.innerHTML = '<div class="mpx-info">Calculating...</div>';
        resultActions.style.display = 'none';
        exportFeeNote.style.display = 'none';
        qrCodeContainer.innerHTML = '';

        const formData = new FormData(form);
        formData.append('action', 'calculate_shipping');
        formData.append('nonce', mpx_ajax.nonce);

        fetch(mpx_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            calculateBtn.disabled = false;
            calculateBtn.classList.remove('mpx-loading');

            if (data.success) {
                currentQuoteData = data;
                const unitText = data.unit === 'CBM' ? `${data.amount} CBM` : data.unit === 'pc' ? `${data.amount} pc` : `${data.amount} kg`;
                const message = `Hello MPX! I need a quote for ${unitText} of ${categorySelect.value} from ${originSelect.options[originSelect.selectedIndex].text} via ${modeSelect.options[modeSelect.selectedIndex].text}. Transit: ${data.transit}. Cost: ${data.currency}${data.cost}. Shipping Mark: ${data.shipping_mark}.`;
                
                let resultHTML = `
                    <div class="mpx-result-card">
                        <h3>Estimated Cost: ${data.currency}${data.cost}</h3>
                        <p><strong>Chargeable ${data.unit}:</strong> ${unitText}</p>
                        <p><strong>Transit Time:</strong> ${data.transit}</p>
                        <p><strong>Shipping Mark:</strong> ${data.shipping_mark}</p>
                        <p><strong>Warehouse Address:</strong> ${data.address}</p>
                        <p><strong>Breakdown:</strong> ${data.breakdown}</p>
                        ${data.note ? `<p class="mpx-note">${data.note}</p>` : ''}
                        <button class="mpx-btn" onclick="copyToClipboard('${data.shipping_mark}')">Copy Shipping Mark</button>
                        <button class="mpx-print-btn" onclick="window.print()">Print Quote</button>
                        <a href="https://wa.me/${data.whatsapp.replace('+', '')}?text=${encodeURIComponent(message)}" class="mpx-btn mpx-whatsapp-btn" target="_blank">WhatsApp Quote</a>
                        <p class="mpx-disclaimer">Rates exclude local delivery from Harare HQ and storage fees after 3 free days. Terms and conditions apply.</p>
                    </div>
                `;
                resultCardContainer.innerHTML = resultHTML;
                resultActions.style.display = 'flex';
                exportFeeNote.style.display = 'block';

                resultDiv.scrollIntoView({ behavior: 'smooth' });
            } else {
                resultCardContainer.innerHTML = `<div class="mpx-error">${data.message || 'An unknown error occurred.'}</div>`;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            calculateBtn.disabled = false;
            calculateBtn.classList.remove('mpx-loading');
            resultCardContainer.innerHTML = '<div class="mpx-error">An error occurred: ' + error.message + '</div>';
        });
    });

    // Reset button
    resetBtn.addEventListener('click', function() {
        form.reset();
        resultDiv.style.display = 'none';
        modeField.style.display = 'none';
        quantityField.style.display = 'none';
        dimensionsDiv.style.display = 'none';
        calculateBtn.disabled = false;
        resultCardContainer.innerHTML = '';
        resultActions.style.display = 'none';
        exportFeeNote.style.display = 'none';
        qrCodeContainer.innerHTML = '';
        currentQuoteData = null;
        calculateBtn.classList.remove('mpx-loading');
        modeSelect.innerHTML = '<option value="">Select Mode</option>';
    });

    // Download PDF button
    downloadPdfBtn.addEventListener('click', function() {
        if (!currentQuoteData) return;
        
        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = mpx_ajax.ajax_url;
        tempForm.style.display = 'none';

        const data = {
            ...Object.fromEntries(new FormData(document.getElementById('mpx-calculator-form'))),
            action: 'generate_pdf',
            nonce: mpx_ajax.pdf_nonce,
            cost: currentQuoteData.cost,
            currency: currentQuoteData.currency,
            transit: currentQuoteData.transit,
            shipping_mark: currentQuoteData.shipping_mark,
            address: currentQuoteData.address,
        };

        for (const key in data) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = data[key];
            tempForm.appendChild(input);
        }

        document.body.appendChild(tempForm);
        tempForm.submit();
        document.body.removeChild(tempForm);
    });

    // Get QR Code button
    getQrBtn.addEventListener('click', function() {
        // If QR code is already visible, hide it and reset button
        if (qrCodeContainer.innerHTML) {
            qrCodeContainer.innerHTML = '';
            getQrBtn.textContent = 'Get Quote QR Code';
            return;
        }

        const formData = new FormData(document.getElementById('mpx-calculator-form'));
        formData.append('action', 'generate_qr');
        formData.append('nonce', mpx_ajax.qr_nonce);
        const currentPageUrl = window.location.href.split('?')[0];
        formData.append('page_url', currentPageUrl);

        getQrBtn.disabled = true;
        getQrBtn.classList.add('mpx-loading');
        getQrBtn.textContent = 'Generating...';

        fetch(mpx_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            getQrBtn.disabled = false;
            getQrBtn.classList.remove('mpx-loading');
            if (data.success && data.data.qr_code) {
                qrCodeContainer.innerHTML = `<img src="${data.data.qr_code}" alt="Quote QR Code" />`;
                getQrBtn.textContent = 'Hide QR Code';
            } else {
                qrCodeContainer.innerHTML = '<p class="mpx-error">Could not generate QR code.</p>';
                getQrBtn.textContent = 'Get Quote QR Code';
            }
        })
        .catch(error => {
            console.error('QR Code error:', error);
            qrCodeContainer.innerHTML = '<p class="mpx-error">An error occurred while generating the QR code.</p>';
            getQrBtn.disabled = false;
            getQrBtn.classList.remove('mpx-loading');
            getQrBtn.textContent = 'Get Quote QR Code';
        });
    });

    function prefillFormFromUrl() {
        const params = new URLSearchParams(window.location.search);
        if (params.has('origin')) {
            let shouldSubmit = false;
            for (const [key, value] of params.entries()) {
                const field = form.elements[key];
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = !!value;
                    } else {
                        field.value = value;
                    }
                    if (key === 'origin') {
                        field.dispatchEvent(new Event('change'));
                    }
                    shouldSubmit = true;
                }
            }
            if (shouldSubmit) {
                setTimeout(() => {
                    if (params.has('mode')) {
                         modeSelect.value = params.get('mode');
                    }
                    if (params.has('category')) {
                        categorySelect.value = params.get('category');
                        categorySelect.dispatchEvent(new Event('change'));
                    }
                     if (params.has('dimensions-toggle')) {
                        dimensionsToggle.checked = true;
                        dimensionsToggle.dispatchEvent(new Event('change'));
                    }
                    form.requestSubmit();
                }, 300);
            }
        }
    }

    prefillFormFromUrl();

    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Shipping Mark copied to clipboard!');
        });
    };
});