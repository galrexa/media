<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>@yield('title') - Media App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('meta_description', 'Halaman error untuk Media App')">
    <meta name="author" content="Media App">

    <!-- Style css -->
    <link href="{{ asset('css/errors/style.min.css') }}" rel="stylesheet" type="text/css">
    
    <style>
        /* Definisi variabel warna */
        :root {
            --primary-color: #4361ee;
            --primary-light: #4cc9f0;
            --primary-dark: #3a56d4;
        }
        
        /* Background gradasi warna biru */
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        .error-gradient-bg {
            background: linear-gradient(135deg, #4361ee, #4cc9f0);
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
        }
        
        .error-container {
            text-align: center;
            max-width: 600px;
            color: white;
            z-index: 1; /* Ensure content is above the canvas */
        }
        
        /* Canvas for particle animation */
        #particle-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Behind the content */
        }
        
        /* 
         * Text code dengan gambar sebagai fill
         * Menggunakan pendekatan yang lebih kompatibel dengan browser
         */
        .text-with-image-fill {
            font-size: 200px;
            font-weight: bold;
            line-height: 1;
            margin-bottom: 1.5rem;
            background-image: url('{{ asset('img/errors/error-1.png') }}');
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            background-size: cover;
            background-position: center;
            display: inline-block;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Fallback untuk browser yang tidak mendukung background-clip: text */
        @supports not (background-clip: text) or 
                  not (-webkit-background-clip: text) {
            .text-with-image-fill {
                color: white;
                background-image: none;
            }
        }
        
        /* Style untuk pesan error */
        .error-message {
            font-size: 2rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1rem;
        }
        
        /* Style untuk deskripsi error */
        .error-description {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        /* Style untuk tombol kembali */
        .back-button {
            display: inline-block;
            padding: 0.75rem 2rem;
            background-color: white;
            color: #4361ee;
            font-weight: 600;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .back-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .text-with-image-fill {
                font-size: 120px;
            }
            
            .error-message {
                font-size: 1.5rem;
            }
            
            .error-description {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="error-gradient-bg">
        <!-- Canvas for particle animation -->
        <canvas id="particle-canvas"></canvas>
        
        <div class="error-container">
            <!-- Error code dengan gambar sebagai fill -->
            <div class="text-with-image-fill">@yield('code')</div>
            
            <!-- Error message -->
            <h2 class="error-message">@yield('message')</h2>
            <p class="error-description">@yield('description')</p>
            
            <!-- Back to homepage button -->
            <a href="{{ url('/') }}" class="back-button">
                Kembali ke Muka
            </a>
            @yield('actions')
        </div>
    </div>

    <script>
        // Particle animation script
        const canvas = document.getElementById('particle-canvas');
        const ctx = canvas.getContext('2d');

        // Set canvas size to match window
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        // Resize canvas on window resize
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        // Particle class
        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 3 + 1; // Small particles
                this.speedX = Math.random() * 1 - 0.5; // Slow movement
                this.speedY = Math.random() * 1 - 0.5;
                this.opacity = Math.random() * 0.5 + 0.3; // Semi-transparent
            }

            update() {
                this.x += this.speedX;
                this.y += this.speedY;

                // Bounce off edges
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;

                // Fade in and out effect
                this.opacity -= 0.005;
                if (this.opacity <= 0) {
                    this.opacity = 0.5;
                    this.x = Math.random() * canvas.width;
                    this.y = Math.random() * canvas.height;
                }
            }

            draw() {
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        // Create particle array
        const particles = [];
        const particleCount = 50; // Subtle number of particles
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }

        // Animation loop
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });
            requestAnimationFrame(animate);
        }

        // Start animation
        animate();
    </script>
</body>
</html>