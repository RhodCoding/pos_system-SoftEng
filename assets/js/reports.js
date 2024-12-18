// Reports Page Management
const ReportsManager = {
    elements: {
        reportDate: document.getElementById('reportDate'),
        hourlySalesChart: document.getElementById('hourlySalesChart'),
        topItemsChart: document.getElementById('topItemsChart'),
        totalSales: document.querySelector('.bg-primary h2'),
        itemsSold: document.querySelector('.bg-success h2'),
        averageSale: document.querySelector('.bg-info h2'),
        salesTable: document.querySelector('.table tbody')
    },

    // Sample data structure for sales
    sampleData: {
        totalSales: 8750.00,
        totalPieces: 235,
        bestSeller: {
            name: 'Pandesal',
            quantity: 150
        },
        hourlyData: [
            300,  // 6AM (early morning bread sales)
            850,  // 7AM (breakfast rush)
            1200, // 8AM (peak breakfast)
            1000, // 9AM
            800,  // 10AM
            750,  // 11AM
            950,  // 12PM (lunch time)
            800,  // 1PM
            600,  // 2PM
            500,  // 3PM
            500,  // 4PM
            500   // 5PM
        ],
        topItems: {
            labels: ['Pandesal', 'Ensaymada', 'Cheese Bread', 'Spanish Bread', 'Chocolate Cake'],
            data: [150, 25, 30, 20, 10]
        },
        salesList: [
            { time: '06:15 AM', items: ['Pandesal x50', 'Spanish Bread x10'], total: 350.00, payment: 'Cash' },
            { time: '07:30 AM', items: ['Pandesal x30', 'Cheese Bread x5', 'Ensaymada x3'], total: 425.00, payment: 'Cash' },
            { time: '08:45 AM', items: ['Chocolate Cake x1', 'Ensaymada x5'], total: 550.00, payment: 'Cash' },
            { time: '09:20 AM', items: ['Pandesal x20', 'Spanish Bread x5'], total: 150.00, payment: 'Cash' },
            { time: '10:15 AM', items: ['Cheese Bread x8', 'Ensaymada x4'], total: 320.00, payment: 'Cash' }
        ]
    },

    // Initialize charts
    initCharts() {
        // Hourly Sales Chart
        this.charts = {};
        this.charts.hourlySales = new Chart(this.elements.hourlySalesChart.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['6AM', '7AM', '8AM', '9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM'],
                datasets: [{
                    label: 'Sales (₱)',
                    data: this.sampleData.hourlyData,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₱' + value
                        }
                    }
                }
            }
        });

        // Top Items Chart
        this.charts.topItems = new Chart(this.elements.topItemsChart.getContext('2d'), {
            type: 'pie',
            data: {
                labels: this.sampleData.topItems.labels,
                datasets: [{
                    data: this.sampleData.topItems.data,
                    backgroundColor: [
                        '#FF9F40',  // Orange for Pandesal
                        '#FF6384',  // Pink for Ensaymada
                        '#36A2EB',  // Blue for Cheese Bread
                        '#FFCD56',  // Yellow for Spanish Bread
                        '#4BC0C0'   // Teal for Chocolate Cake
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    // Update dashboard with new data
    updateDashboard(data = this.sampleData) {
        // Update summary cards
        this.elements.totalSales.textContent = '₱' + data.totalSales.toFixed(2);
        this.elements.itemsSold.textContent = data.totalPieces + ' pcs';
        this.elements.averageSale.textContent = data.bestSeller.name;
        document.querySelector('.bg-info small').textContent = data.bestSeller.quantity + ' pcs sold';

        // Update sales table
        this.elements.salesTable.innerHTML = data.salesList.map(sale => `
            <tr>
                <td>${sale.time}</td>
                <td>${sale.items.join(', ')}</td>
                <td>₱${sale.total.toFixed(2)}</td>
                <td><span class="badge bg-success">Cash</span></td>
            </tr>
        `).join('');

        // Update table footer
        document.querySelector('table tfoot strong:last-child').textContent = 
            '₱' + data.totalSales.toFixed(2);
    },

    // Initialize
    init() {
        this.initCharts();
        this.updateDashboard();

        // Event listeners
        this.elements.reportDate.addEventListener('change', () => {
            // In the future, this will fetch real data for the selected date
            this.updateDashboard();
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => ReportsManager.init());
