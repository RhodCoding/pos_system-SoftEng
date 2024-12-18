// Simple utility functions for Bakery POS

const BakeryUtils = {
    // Format currency (PHP)
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    },
    
    // Print receipt
    printReceipt(items, total, cashReceived, change) {
        const receiptWindow = window.open('', '_blank');
        const date = new Date().toLocaleString();
        
        const receiptContent = `
            <html>
            <head>
                <title>Bakery Receipt</title>
                <style>
                    body { font-family: monospace; padding: 20px; }
                    .text-center { text-align: center; }
                    .mb-3 { margin-bottom: 15px; }
                    .border-bottom { border-bottom: 1px dashed #000; }
                </style>
            </head>
            <body>
                <div class="text-center mb-3">
                    <h2>Bakery Receipt</h2>
                    <p>${date}</p>
                </div>
                <div class="border-bottom mb-3"></div>
                ${items.map(item => `
                    <div>
                        ${item.name} x ${item.quantity}
                        <span style="float: right;">₱${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                `).join('')}
                <div class="border-bottom mb-3"></div>
                <div>
                    <strong>Total:</strong> <span style="float: right;">₱${total.toFixed(2)}</span>
                </div>
                <div>
                    <strong>Cash:</strong> <span style="float: right;">₱${cashReceived.toFixed(2)}</span>
                </div>
                <div>
                    <strong>Change:</strong> <span style="float: right;">₱${change.toFixed(2)}</span>
                </div>
                <div class="border-bottom mb-3"></div>
                <div class="text-center">
                    <p>Thank you for your purchase!</p>
                    <p>Please come again!</p>
                </div>
            </body>
            </html>
        `;
        
        receiptWindow.document.write(receiptContent);
        receiptWindow.document.close();
        setTimeout(() => {
            receiptWindow.print();
            receiptWindow.close();
        }, 250);
    }
};

// Export utils for use in other files
window.BakeryUtils = BakeryUtils;
