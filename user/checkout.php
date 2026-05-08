<?php
include 'auth_check.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? "customer@example.com";
$user_name = $_SESSION['name'];
// --- SAVE ADDRESS FROM CHECKOUT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_checkout_address'])) {
    $addr = $conn->real_escape_string($_POST['address']);

    if(!empty($addr)){
        $conn->query("UPDATE users SET address = '$addr' WHERE id = $user_id");
    }
    exit(); // IMPORTANT (AJAX ke liye)
}

// 1. Calculate Grand Total
$sql = "SELECT c.quantity, p.price, p.discount_price 
        FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = $user_id";
$res = $conn->query($sql);
$total_in_rupee = 0;

while($row = $res->fetch_assoc()) {
    $price = ($row['discount_price'] > 0) ? $row['discount_price'] : $row['price'];
    $total_in_rupee += ($price * $row['quantity']);
}

// Add 5% tax
$total_with_tax = $total_in_rupee * 1.05;

if($total_with_tax <= 0) { header("Location: cart.php"); exit(); }
// FETCH USER ADDRESS
$user_data = $conn->query("SELECT address FROM users WHERE id = $user_id")->fetch_assoc();
$user_address = $user_data['address'] ?? "";
$amount_in_paisa = round($total_with_tax * 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout | CartHub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Razorpay -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <style>
        * { font-family: 'Outfit', sans-serif; }

        body {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding-top: 50px;
            color: #ffffff;
        }

        .shipping-card {
            background: #111827;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #374151;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            transition: 0.3s;
        }

        .shipping-card:hover { transform: translateY(-5px); }

        h4, h5 { color: #ffffff; font-weight: 600; }

        label {
            color: #e5e7eb;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-control {
            background: #1f2937;
            border: 1px solid #4b5563;
            color: #ffffff;
            border-radius: 10px;
            padding: 12px;
        }

        .form-control:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 8px rgba(56,189,248,0.5);
        }

        .error {
            color: #ff4d4d;
            font-size: 13px;
            margin-top: 4px;
        }

        .summary-card {
            background: #111827;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #374151;
        }

        .price-summary {
            background: #1f2937;
            border-radius: 15px;
            padding: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            border: none;
            border-radius: 30px;
            font-weight: 600;
        }

        .btn-primary:hover { transform: scale(1.05); }

        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }

        .rzp-logo { margin-top: 10px; width: 120px; }
    </style>
</head>

<body>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="row g-4">

                <!-- SHIPPING -->
                <div class="col-md-7">
                    <div class="shipping-card">
                        <h4 class="mb-4"><i class="fas fa-truck me-2 text-info"></i>Shipping Details</h4>

                        <form id="addressForm" novalidate>
                            <div class="row g-3">

                                <div class="col-12">
                                    <label>FULL NAME</label>
                                    <input type="text" id="cust_name" class="form-control" value="<?php echo $user_name; ?>">
                                    <div class="error" id="nameError"></div>
                                </div>

                                <div class="col-12">
                                    <label>STREET ADDRESS / HOUSE NO.</label>
                                   <input type="text" id="address" class="form-control"
value="<?php echo htmlspecialchars($user_address); ?>"
placeholder="Building Name, Street, Area">
                                    <div class="error" id="addressError"></div>
                                </div>

                                <!-- ✅ STATE -->
                                <div class="col-md-6">
                                    <label>STATE</label>
                                    <select id="state" class="form-control">
                                        <option value="">Select State</option>
                                        <option>Gujarat</option>
                                        <option>Maharashtra</option>
                                        <option>Delhi</option>
                                        <option>Rajasthan</option>
                                        <option>Karnataka</option>
                                        <option>Tamil Nadu</option>
                                        <option>Uttar Pradesh</option>
                                        <option>Madhya Pradesh</option>
                                        <option>Punjab</option>
                                        <option>Haryana</option>
                                    </select>
                                    <div class="error" id="stateError"></div>
                                </div>

                                <!-- ✅ CITY -->
                                <div class="col-md-6">
                                    <label>CITY</label>
                                    <select id="city" class="form-control">
                                        <option value="">Select City</option>
                                    </select>
                                    <div class="error" id="cityError"></div>
                                </div>

                                <div class="col-md-6">
                                    <label>PINCODE</label>
                                    <input type="number" id="pincode" class="form-control" placeholder="6 Digits">
                                    <div class="error" id="pincodeError"></div>
                                </div>

                                <div class="col-12">
                                    <label>PHONE NUMBER</label>
                                    <input type="tel" id="phone" class="form-control" placeholder="10-digit mobile number">
                                    <div class="error" id="phoneError"></div>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

                <!-- SUMMARY -->
                <div class="col-md-5">
                    <div class="summary-card">
                        <h5 class="mb-4">Order Summary</h5>

                        <div class="price-summary mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Items:</span>
                                <span><?php echo $res->num_rows; ?></span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Net Amount:</span>
                                <span>₹<?php echo number_format($total_in_rupee, 2); ?></span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>GST (5%):</span>
                                <span>₹<?php echo number_format($total_in_rupee * 0.05, 2); ?></span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <span>Final Payable:</span>
                                <span class="fs-4 text-info">₹<?php echo number_format($total_with_tax, 2); ?></span>
                            </div>
                        </div>

                        <button id="rzp-button1" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-shield-check me-2"></i>Pay with Razorpay
                        </button>

                        <div class="text-center">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/8/89/Razorpay_logo.svg" class="rzp-logo">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function showError(id, msg){ document.getElementById(id).innerText = msg; }
function clearError(id){ document.getElementById(id).innerText = ""; }

let cust_name=document.getElementById('cust_name');
let addressField=document.getElementById('address');
let cityField=document.getElementById('city');
let stateField=document.getElementById('state');
let pincodeField=document.getElementById('pincode');
let phoneField=document.getElementById('phone');

/* ✅ ALL STATES (INDIA) */
const states = [
"Andhra Pradesh","Arunachal Pradesh","Assam","Bihar","Chhattisgarh","Goa",
"Gujarat","Haryana","Himachal Pradesh","Jharkhand","Karnataka","Kerala",
"Madhya Pradesh","Maharashtra","Manipur","Meghalaya","Mizoram","Nagaland",
"Odisha","Punjab","Rajasthan","Sikkim","Tamil Nadu","Telangana","Tripura",
"Uttar Pradesh","Uttarakhand","West Bengal","Delhi","Jammu and Kashmir",
"Ladakh","Puducherry","Chandigarh","Andaman and Nicobar Islands",
"Dadra and Nagar Haveli and Daman and Diu","Lakshadweep"
];

/* 🔥 STATE DROPDOWN AUTO FILL */
stateField.innerHTML = '<option value="">Select State</option>';
states.forEach(s=>{
    stateField.innerHTML += `<option value="${s}">${s}</option>`;
});
/* 🔥 STATE → CITY (API BASED - REAL DATA) */
stateField.addEventListener("change", function(){
    let state = this.value;

    cityField.innerHTML = '<option>Loading...</option>';

    fetch("https://countriesnow.space/api/v0.1/countries/state/cities", {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ country:"India", state: state })
    })
    .then(res=>res.json())
    .then(data=>{
        cityField.innerHTML='<option value="">Select City</option>';

        if(data.data){
            data.data.forEach(city=>{
                cityField.innerHTML += `<option>${city}</option>`;
            });
        }
    })
    .catch(()=>{
        cityField.innerHTML='<option value="">Select City</option>';
    });
});

/* 🔥 PINCODE AUTO CITY + STATE */
pincodeField.addEventListener("input", function(){
    this.value = this.value.replace(/\D/g,'').slice(0,6);

    if(this.value.length !== 6){
        cityField.innerHTML='<option value="">Select City</option>';
        stateField.value="";
        return;
    }

    fetch("https://api.postalpincode.in/pincode/" + this.value)
    .then(res=>res.json())
    .then(data=>{
        if(data[0].Status === "Success"){
            let post = data[0].PostOffice[0];

            let state = post.State;
            let city = post.District;

            stateField.value = state;

            // Load cities of that state first
            fetch("https://countriesnow.space/api/v0.1/countries/state/cities", {
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                body: JSON.stringify({ country:"India", state: state })
            })
            .then(res=>res.json())
            .then(cData=>{
                cityField.innerHTML='<option value="">Select City</option>';

                if(cData.data){
                    cData.data.forEach(c=>{
                        if(c === city){
                            cityField.innerHTML += `<option selected>${c}</option>`;
                        } else {
                            cityField.innerHTML += `<option>${c}</option>`;
                        }
                    });
                }
            });

            clearError('pincodeError');
        } else {
            showError('pincodeError','Invalid pincode');
            stateField.value="";
            cityField.innerHTML='<option value="">Select City</option>';
        }
    });
});

/* VALIDATION (UNCHANGED) */
function validate(){
    let valid = true;

    let name = cust_name.value.trim();
    let address = addressField.value.trim();
    let city = cityField.value.trim();
    let pincode = pincodeField.value.trim();
    let phone = phoneField.value.trim();

    let regex = /^[A-Za-z ]+$/;

    if(name=="" || !regex.test(name)){
        showError('nameError', name==""?"This field must be filled":"Only characters allowed"); valid=false;
    } else clearError('nameError');

    if(address==""){ showError('addressError','This field must be filled'); valid=false;}
    else clearError('addressError');

    if(city==""){
        showError('cityError',"This field must be filled"); valid=false;
    } else clearError('cityError');

    if(pincode==""){
        showError('pincodeError','This field must be filled'); valid=false;
    } else if(pincode.length!=6){
        showError('pincodeError','Pincode must be 6 digits'); valid=false;
    } else clearError('pincodeError');

    if(phone.length!=10){
        showError('phoneError','Mobile number should be 10 digit'); valid=false;
    } else clearError('phoneError');
if(stateField.value==""){
    showError('stateError',"Select state"); 
    valid=false;
} else clearError('stateError');
    return valid;
}

document.querySelectorAll("input, select").forEach(i=>{
    i.addEventListener("input",validate);
});

/* RAZORPAY (UNCHANGED) */
var options = {
    "key": "rzp_test_SPRbM48uGd6FEp",
    "amount": "<?php echo $amount_in_paisa; ?>",
    "currency": "INR",
    "name": "CartHub Store",
    "description": "Secure Purchase",
    "image": "https://cdn-icons-png.flaticon.com/512/1162/1162499.png",
    "prefill": {
        "name": "<?php echo $user_name; ?>",
        "email": "<?php echo $user_email; ?>"
    },
    "theme": { "color": "#2563eb" },
    "handler": function (response){
        var addr = addressField.value+", "+cityField.value+" - "+pincodeField.value;
        var phone = phoneField.value;

        window.location.href = "place_order.php?payment_id="+response.razorpay_payment_id+
        "&address="+encodeURIComponent(addr)+"&phone="+encodeURIComponent(phone);
    }
};

var rzp1 = new Razorpay(options);

document.getElementById('rzp-button1').onclick = function(e){
    if(!validate()) return;

    let addr = addressField.value + ", " + cityField.value + " - " + pincodeField.value;

    // SAVE ADDRESS IN DATABASE
    fetch("save_address.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "address=" + encodeURIComponent(addr)
    })
    .then(() => {
        rzp1.open(); // THEN OPEN PAYMENT
    });

    e.preventDefault();
}
</script>

</body>
</html>