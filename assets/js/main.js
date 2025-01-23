// Common JavaScript functions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById(previewId).src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-subtotal').forEach(function (element) {
        total += parseFloat(element.value) || 0;
    });
    document.getElementById('total_amount').value = total.toFixed(2);
}

function updateSubtotal(row) {
    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const subtotal = quantity * price;
    row.querySelector('.item-subtotal').value = subtotal.toFixed(2);
    calculateTotal();
}

// AJAX functions for dynamic data loading
function fetchItemDetails(itemId, targetRow) {
    $.ajax({
        url: 'ajax/get_item_details.php',
        type: 'POST',
        data: { item_id: itemId },
        success: function (response) {
            const data = JSON.parse(response);
            targetRow.querySelector('.item-price').value = data.unit_price;
            updateSubtotal(targetRow);
        }
    });
}

function addItemRow() {
    const template = document.getElementById('item-row-template');
    const newRow = template.content.cloneNode(true);
    document.getElementById('items-container').appendChild(newRow);
}

// Date range picker initialization
$(document).ready(function () {
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }
    if ($.fn.daterangepicker) {
        $('.date-range-picker').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });
    }

    // Initialize select2 for searchable dropdowns
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%'
        });
    }

    // Initialize DataTables
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            "responsive": true,
            "order": [[0, "desc"]],
            "pageLength": 25
        });
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Stock management functions
function updateStock(itemId, quantity, operation) {
    $.ajax({
        url: 'ajax/update_stock.php',
        type: 'POST',
        data: {
            item_id: itemId,
            quantity: quantity,
            operation: operation
        },
        success: function (response) {
            const data = JSON.parse(response);
            if (data.success) {
                alert('Stock updated successfully');
                location.reload();
            } else {
                alert('Error updating stock: ' + data.message);
            }
        }
    });
}

// Invoice generation functions
function generateInvoice(type) {
    if (!validateForm('invoice-form')) {
        alert('Please fill in all required fields');
        return false;
    }

    const form = document.getElementById('invoice-form');
    const formData = new FormData(form);

    $.ajax({
        url: 'ajax/generate_invoice.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            const data = JSON.parse(response);
            if (data.success) {
                window.location.href = 'view_invoice.php?id=' + data.invoice_id + '&type=' + type;
            } else {
                alert('Error generating invoice: ' + data.message);
            }
        }
    });

    return false;
}

// Employee salary calculation
function calculateSalary(employeeId, month) {
    $.ajax({
        url: 'ajax/calculate_salary.php',
        type: 'POST',
        data: {
            employee_id: employeeId,
            month: month
        },
        success: function (response) {
            const data = JSON.parse(response);
            if (data.success) {
                document.getElementById('regular_hours').value = data.regular_hours;
                document.getElementById('overtime_hours').value = data.overtime_hours;
                document.getElementById('regular_amount').value = data.regular_amount;
                document.getElementById('overtime_amount').value = data.overtime_amount;
                document.getElementById('total_salary').value = data.total_salary;
            } else {
                alert('Error calculating salary: ' + data.message);
            }
        }
    });
}

// Add this to your existing JavaScript
function updateSubtotal(row) {
    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const subtotal = quantity * price;
    row.querySelector('.item-subtotal').value = subtotal.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-subtotal').forEach(function (element) {
        total += parseFloat(element.value) || 0;
    });
    document.getElementById('total-amount').textContent = 'Rs. ' + total.toFixed(2);
}

// Initialize Select2 with search and create option
$('.item-select').select2({
    tags: true,
    createTag: function (params) {
        return {
            id: 'new:' + params.term,
            text: params.term,
            newOption: true
        };
    }
}).on('select2:select', function (e) {
    const data = e.params.data;
    if (data.newOption) {
        const itemName = data.text;
        promptNewItem(this, itemName);
    } else {
        const price = $(this).find(':selected').data('price');
        $(this).closest('tr').find('.item-price').val(price);
        updateSubtotal($(this).closest('tr')[0]);
    }
});

function promptNewItem(selectElement, itemName) {
    const price = prompt('Enter price for new item "' + itemName + '":');
    if (price !== null) {
        // Add new item to database via AJAX
        $.ajax({
            url: 'ajax/add_item.php',
            method: 'POST',
            data: {
                name: itemName,
                unit_price: price,
                stock_quantity: 0,
                minimum_stock: 0
            },
            success: function (response) {
                const data = JSON.parse(response);
                if (data.success) {
                    // Update select with new item
                    const newOption = new Option(itemName, data.item_id, true, true);
                    $(selectElement).append(newOption).trigger('change');
                    $(selectElement).closest('tr').find('.item-price').val(price);
                    updateSubtotal($(selectElement).closest('tr')[0]);
                }
            }
        });
    }
}

document.querySelector('input[name="payment_amount"]').addEventListener('change', function () {
    const max = parseFloat(this.getAttribute('max'));
    if (parseFloat(this.value) > max) {
        alert('Payment amount cannot exceed the outstanding amount');
        this.value = max;
    }
});

function previewImages(input) {
    const previewContainer = document.getElementById('image-previews');
    previewContainer.innerHTML = ''; // Clear existing previews

    if (input.files && input.files.length > 0) {
        for (let i = 0; i < input.files.length; i++) {
            const reader = new FileReader();
            const preview = document.createElement('div');
            preview.className = 'd-inline-block m-2 position-relative';

            reader.onload = function (e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" class="img-thumbnail" style="height: 100px;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" 
                            onclick="removeImage(this, ${i})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
            };

            reader.readAsDataURL(input.files[i]);
            previewContainer.appendChild(preview);
        }
    }
}

function removeImage(button, index) {
    const input = document.querySelector('input[name="repair_photos[]"]');
    const container = document.getElementById('image-previews');

    // Create a new DataTransfer object
    const dt = new DataTransfer();

    // Add all files except the one to remove
    for (let i = 0; i < input.files.length; i++) {
        if (i !== index) {
            dt.items.add(input.files[i]);
        }
    }

    // Set the new FileList
    input.files = dt.files;

    // Remove the preview
    button.closest('.d-inline-block').remove();
}

function confirmDelete(paymentId, month) {
    if (confirm('Are you sure you want to delete this salary payment record? This action cannot be undone.')) {
        window.location.href = `delete_salary_payment.php?id=${paymentId}&month=${month}`;
    }
}


