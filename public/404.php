<?php
require_once __DIR__ . '/../core/Config/Env.php';
$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/public$#', '', $scriptDir);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Page Not Found</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        /* Default cursor */

        /* Ghost Container */
        .ghost-container {
            position: fixed;
            left: 50%;
            top: 30%;
            transform: translate(-50%, -50%);
            transition: all 0.15s ease-out;
            z-index: 100;
        }

        .ghost-container.fleeing {
            transition: all 0.12s ease-out;
        }

        .ghost-container.escaped {
            transition: all 0.6s ease-in;
            opacity: 0;
            transform: scale(0.3) rotate(720deg) !important;
        }

        /* Ghost Body */
        .ghost {
            position: relative;
            width: 120px;
            height: 145px;
            background: #fff;
            border-radius: 80px 80px 0 0;
            box-shadow:
                inset -8px -8px 25px rgba(0, 0, 0, 0.03),
                0 0 35px rgba(162, 155, 254, 0.15);
            animation: float 3s ease-in-out infinite;
        }

        .ghost.scared {
            animation: shake 0.1s linear infinite;
        }

        /* Bottom - Smooth rounded */
        .ghost::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 10%;
            width: 80%;
            height: 40px;
            background: #fff;
            border-radius: 0 0 50% 50%;
        }

        /* Eyes */
        .eyes {
            display: flex;
            justify-content: center;
            gap: 25px;
            padding-top: 50px;
        }

        .eye {
            width: 12px;
            height: 12px;
            background: #2d3436;
            border-radius: 50%;
            animation: blink 4s ease-in-out infinite;
        }

        .ghost.scared .eye {
            animation: none;
            width: 10px;
            height: 18px;
            border-radius: 50%;
        }

        /* Mouth */
        .mouth {
            width: 12px;
            height: 6px;
            background: #2d3436;
            border-radius: 0 0 10px 10px;
            margin: 8px auto 0;
        }

        .ghost.scared .mouth {
            border-radius: 50%;
            width: 14px;
            height: 14px;
            animation: scream 0.2s ease-in-out infinite;
        }

        /* Cheeks */
        .blush {
            position: absolute;
            top: 62px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 22px;
            box-sizing: border-box;
        }

        .cheek {
            width: 15px;
            height: 7px;
            background: #FFB7B2;
            border-radius: 50%;
            opacity: 0.5;
            transition: opacity 0.3s;
        }

        .ghost.scared .cheek {
            opacity: 0;
        }

        /* Sweat Drop */
        .sweat {
            position: absolute;
            top: 45px;
            right: 15px;
            width: 8px;
            height: 12px;
            background: #74b9ff;
            border-radius: 0 50% 50% 50%;
            transform: rotate(45deg);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .ghost.scared .sweat {
            opacity: 0.8;
            animation: sweatDrop 0.5s ease-in-out infinite;
        }

        /* Shadow */
        .shadow-ghost {
            width: 80px;
            height: 12px;
            background: rgba(0, 0, 0, 0.08);
            border-radius: 50%;
            margin: 30px auto 0;
            animation: shadowPulse 3s ease-in-out infinite;
            filter: blur(2px);
        }

        /* Animations */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-12px);
            }
        }

        @keyframes blink {

            0%,
            90%,
            100% {
                transform: scaleY(1);
            }

            95% {
                transform: scaleY(0.1);
            }
        }

        @keyframes shadowPulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.1;
            }

            50% {
                transform: scale(0.85);
                opacity: 0.06;
            }
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-3px);
            }

            75% {
                transform: translateX(3px);
            }
        }

        @keyframes scream {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        @keyframes sweatDrop {
            0% {
                transform: rotate(45deg) translateY(0);
                opacity: 0.8;
            }

            100% {
                transform: rotate(45deg) translateY(10px);
                opacity: 0;
            }
        }

        /* Speech Bubble */
        .speech {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%) scale(0);
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            white-space: nowrap;
            opacity: 0;
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .speech::after {
            content: '';
            position: absolute;
            bottom: -7px;
            left: 50%;
            transform: translateX(-50%);
            border: 7px solid transparent;
            border-top-color: #fff;
        }

        .ghost.scared .speech {
            transform: translateX(-50%) scale(1);
            opacity: 1;
        }

        /* Return Message */
        .return-message {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.1rem;
            color: #888;
            opacity: 0;
            transition: opacity 0.5s;
            pointer-events: none;
            text-align: center;
        }

        .return-message.visible {
            opacity: 1;
        }
    </style>
</head>

<body class="bg-gray-50 h-screen w-full flex flex-col items-center justify-end font-nunito text-gray-600 overflow-hidden select-none pb-16">

    <!-- Ghost -->
    <div class="ghost-container" id="ghostContainer">
        <div class="ghost" id="ghost">
            <div class="speech">อย่ามาใกล้! กลัว!</div>
            <div class="sweat"></div>
            <div class="eyes">
                <div class="eye"></div>
                <div class="eye"></div>
            </div>
            <div class="blush">
                <div class="cheek"></div>
                <div class="cheek"></div>
            </div>
            <div class="mouth"></div>
        </div>
        <div class="shadow-ghost"></div>
    </div>

    <!-- Return Message -->
    <div class="return-message" id="returnMessage">👻 น้องผีหนีไปแล้ว...<br>รอสักครู่นะ</div>

    <!-- Content (fixed at bottom) -->
    <div class="fixed bottom-0 left-0 right-0 text-center pb-12 bg-gradient-to-t from-gray-50 via-gray-50 to-transparent pt-20 z-10">
        <h1 class="text-5xl font-bold font-fredoka text-gray-800 mb-2">404</h1>
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Whoops! Nothing here.</h2>
        <p class="text-gray-500 mb-8 max-w-sm mx-auto leading-relaxed text-sm">
            The page you are looking for seems to have floated away...
        </p>
        <a href="<?php echo $basePath; ?>/" class="inline-flex items-center px-6 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-full font-bold hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm hover:shadow-md text-sm">
            Go Home
        </a>
    </div>

    <script>
        const ghostContainer = document.getElementById('ghostContainer');
        const ghost = document.getElementById('ghost');
        const returnMessage = document.getElementById('returnMessage');

        let ghostX = window.innerWidth / 2;
        let ghostY = window.innerHeight * 0.3;
        let mouseX = 0;
        let mouseY = 0;
        let isScared = false;
        let chaseTime = 0;
        let escaped = false;
        const ESCAPE_THRESHOLD = 3000;
        const SAFE_DISTANCE = 150;

        ghostContainer.style.left = ghostX + 'px';
        ghostContainer.style.top = ghostY + 'px';
        ghostContainer.style.transform = 'translate(-50%, -50%)';

        document.addEventListener('mousemove', (e) => {
            if (escaped) return;

            mouseX = e.clientX;
            mouseY = e.clientY;

            const dx = mouseX - ghostX;
            const dy = mouseY - ghostY;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < SAFE_DISTANCE) {
                if (!isScared) {
                    isScared = true;
                    ghost.classList.add('scared');
                    ghostContainer.classList.add('fleeing');
                }

                const angle = Math.atan2(dy, dx);
                const pushDistance = (SAFE_DISTANCE - distance) * 0.5;

                let newX = ghostX - Math.cos(angle) * pushDistance;
                let newY = ghostY - Math.sin(angle) * pushDistance;

                const margin = 80;
                newX = Math.max(margin, Math.min(window.innerWidth - margin, newX));
                newY = Math.max(margin, Math.min(window.innerHeight - 200, newY));

                ghostX = newX;
                ghostY = newY;

                ghostContainer.style.left = ghostX + 'px';
                ghostContainer.style.top = ghostY + 'px';

                chaseTime += 16;
                if (chaseTime > ESCAPE_THRESHOLD) {
                    escapeGhost();
                }
            } else {
                if (isScared) {
                    isScared = false;
                    ghost.classList.remove('scared');
                    ghostContainer.classList.remove('fleeing');
                    chaseTime = Math.max(0, chaseTime - 50);
                }
            }
        });

        function escapeGhost() {
            escaped = true;
            ghostContainer.classList.add('escaped');
            returnMessage.classList.add('visible');

            setTimeout(() => {
                ghostX = window.innerWidth / 2;
                ghostY = window.innerHeight * 0.3;
                ghostContainer.style.left = ghostX + 'px';
                ghostContainer.style.top = ghostY + 'px';

                ghostContainer.classList.remove('escaped');
                ghost.classList.remove('scared');
                ghostContainer.classList.remove('fleeing');
                returnMessage.classList.remove('visible');

                escaped = false;
                isScared = false;
                chaseTime = 0;
            }, 3000);
        }

        window.addEventListener('resize', () => {
            if (!escaped) {
                ghostX = Math.min(ghostX, window.innerWidth - 80);
                ghostY = Math.min(ghostY, window.innerHeight - 200);
            }
        });
    </script>
</body>

</html>