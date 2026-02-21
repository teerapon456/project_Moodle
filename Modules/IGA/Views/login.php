<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - INTEQC GLOBAL ASSESSMENT</title>
    <meta name="theme-color" content="#991B1B">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Tailwind -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 8px;
            /* Slightly sharp corners like the image */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .card-header {
            background-color: #991B1B;
            /* Darker Red matching image */
            padding: 30px 20px;
            text-align: center;
            color: white;
            position: relative;
        }

        .header-icon-circle {
            width: 70px;
            height: 70px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: #991B1B;
            font-size: 32px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .input-icon {
            position: absolute;
            left: 0;
            top: 28px;
            /* Adjust based on label height */
            bottom: 0;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-right: none;
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
            color: #555;
            height: 42px;
            /* Match input height */
        }

        .form-control-icon {
            padding-left: 50px;
            /* Space for icon */
            height: 42px;
        }

        .lang-switch {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }

        /* Tab Pills Styled with Slider Animation */
        .tab-pills {
            display: flex;
            background: #e5e7eb;
            border-radius: 99px;
            padding: 4px;
            margin-bottom: 20px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            position: relative;
        }

        .tab-slider {
            position: absolute;
            top: 4px;
            left: 4px;
            height: calc(100% - 8px);
            width: calc(50% - 4px);
            background: white;
            border-radius: 99px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 0;
        }

        .tab-slider.slide-right {
            transform: translateX(100%);
        }

        .tab-pill {
            padding: 6px 20px;
            border-radius: 99px;
            font-size: 13px;
            cursor: pointer;
            color: #6b7280;
            transition: color 0.3s ease, font-weight 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .tab-pill.active {
            color: #991B1B;
            font-weight: 500;
        }

        .btn-red {
            background-color: #991B1B;
            color: white;
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.2s;
        }

        .btn-red:hover {
            background-color: #7f1d1d;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <!-- Header -->
        <div class="card-header">
            <div class="lang-switch">
                <i class="ri-global-line"></i> English <i class="ri-arrow-down-s-fill"></i>
            </div>

            <div class="header-icon-circle">
                <i class="ri-clipboard-line"></i>
            </div>
            <h1 class="text-2xl font-bold mb-1">Login</h1>
            <p class="text-xs opacity-90 tracking-wide uppercase">Sign in to INTEQC GLOBAL ASSESSMENT</p>
        </div>

        <!-- Body -->
        <div class="p-8 pb-6">

            <!-- Employee/Applicant Toggle with Sliding Animation -->
            <div class="tab-pills">
                <div class="tab-slider" id="tab-slider"></div>
                <div class="tab-pill active" id="pill-emp" onclick="setTab('employee')">Employee</div>
                <div class="tab-pill" id="pill-app" onclick="setTab('applicant')">Applicant</div>
            </div>

            <!-- Error -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-50 text-red-700 text-xs p-3 rounded mb-4 text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Activation Success -->
            <?php if (!empty($_SESSION['activation_success'])): ?>
                <div class="bg-green-50 text-green-700 text-xs p-3 rounded mb-4 text-center">
                    <i class="ri-checkbox-circle-fill mr-1"></i>
                    <?= htmlspecialchars($_SESSION['activation_success']) ?>
                </div>
                <?php unset($_SESSION['activation_success']); ?>
            <?php endif; ?>

            <form action="?page=login&action=check" method="POST" id="login-form">
                <input type="hidden" name="user_type" id="user_type" value="employee">

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Username</label>
                    <div class="relative flex">
                        <div class="flex items-center justify-center bg-gray-100 border border-r-0 border-gray-300 rounded-l px-3">
                            <i class="ri-user-fill text-gray-500"></i>
                        </div>
                        <input type="text" name="username" class="flex-1 px-3 py-2 border border-gray-300 rounded-r focus:outline-none focus:border-red-800 text-sm" placeholder="Username" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Password</label>
                    <div class="relative flex">
                        <div class="flex items-center justify-center bg-gray-100 border border-r-0 border-gray-300 rounded-l px-3">
                            <i class="ri-lock-fill text-gray-500"></i>
                        </div>
                        <input type="password" name="password" id="password" class="flex-1 px-3 py-2 border border-l-0 border-gray-300 focus:outline-none focus:border-red-800 text-sm" placeholder="••••••••" required>
                        <div class="flex items-center justify-center bg-white border border-l-0 border-gray-300 rounded-r px-3 cursor-pointer" onclick="togglePassword()">
                            <i class="ri-eye-line text-gray-400 hover:text-gray-600" id="eye-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-6 text-sm">
                    <label class="flex items-center text-gray-600 cursor-pointer">
                        <input type="checkbox" class="mr-2 rounded text-red-800 focus:ring-red-800">
                        Remember me
                    </label>

                    <div class="text-right text-xs">
                        <a href="#" class="block text-red-800 hover:underline mb-1">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="btn-red text-lg shadow-lg mb-6">
                    LOGIN
                </button>

                <!-- Footer Links -->
                <div class="text-center text-sm text-gray-600 mb-6">
                    Don't have an account yet? <a href="?page=register" class="text-blue-600 font-bold hover:underline">Create an account here</a>
                </div>

                <div class="border-t pt-4 text-center">
                    <a href="#" class="block text-blue-500 text-sm font-medium hover:underline mb-1">User Guide for Applicants</a>
                    <a href="#" class="block text-blue-500 text-sm font-medium hover:underline">User Guide for Associates</a>
                </div>

            </form>
        </div>
    </div>

    <script>
        function setTab(type) {
            document.getElementById('user_type').value = type;
            const slider = document.getElementById('tab-slider');

            if (type === 'employee') {
                document.getElementById('pill-emp').classList.add('active');
                document.getElementById('pill-app').classList.remove('active');
                slider.classList.remove('slide-right');
            } else {
                document.getElementById('pill-app').classList.add('active');
                document.getElementById('pill-emp').classList.remove('active');
                slider.classList.add('slide-right');
            }
        }

        function togglePassword() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>

</html>