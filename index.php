<?php 
    include "db_connection.php";
    include "app/model/report.php";
    $num_reports = count_all_reports($conn);
    $num_resolved_reports = count_reports($conn, "resolved");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token">
    <title>WhistleGuard | Anonymous Whistleblower Platform</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0e1a;
            color: #ffffff;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Floating Shapes Animation Container */
        .shapes-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        
        /* Individual Floating Shapes */
        .shape {
            position: absolute;
            background: rgba(79, 70, 229, 0.08);
            border-radius: 50%;
            animation: floatAround 20s infinite ease-in-out;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.15), rgba(79, 70, 229, 0.05));
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: 10%;
            right: -50px;
            animation-delay: 2s;
            background: radial-gradient(circle, rgba(124, 58, 237, 0.12), rgba(124, 58, 237, 0.04));
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            top: 40%;
            right: 20%;
            animation-delay: 5s;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.08), rgba(16, 185, 129, 0.03));
        }
        
        .shape-4 {
            width: 400px;
            height: 400px;
            bottom: -150px;
            left: -100px;
            animation-delay: 1s;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.06), rgba(79, 70, 229, 0.02));
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }
        
        .shape-5 {
            width: 120px;
            height: 120px;
            top: 20%;
            left: 15%;
            animation-delay: 7s;
            background: rgba(139, 92, 246, 0.08);
            border-radius: 30% 70% 50% 50% / 30% 40% 60% 70%;
        }
        
        .shape-6 {
            width: 250px;
            height: 250px;
            bottom: 30%;
            right: 10%;
            animation-delay: 3s;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.08), rgba(59, 130, 246, 0.03));
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
        }
        
        .shape-7 {
            width: 80px;
            height: 80px;
            top: 70%;
            left: 30%;
            animation-delay: 4s;
            background: rgba(236, 72, 153, 0.06);
            border-radius: 50%;
        }
        
        .shape-8 {
            width: 180px;
            height: 180px;
            top: 15%;
            right: 30%;
            animation-delay: 6s;
            background: rgba(79, 70, 229, 0.07);
            border-radius: 40% 60% 60% 40% / 40% 50% 50% 60%;
        }
        
        @keyframes floatAround {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(30px, -30px) rotate(5deg);
            }
            50% {
                transform: translate(-20px, 20px) rotate(-3deg);
            }
            75% {
                transform: translate(20px, 30px) rotate(2deg);
            }
            100% {
                transform: translate(0, 0) rotate(0deg);
            }
        }
        
        /* Slow floating for larger shapes */
        @keyframes slowFloat {
            0% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(40px, -20px) scale(1.05);
            }
            100% {
                transform: translate(0, 0) scale(1);
            }
        }
        
        .shape-1, .shape-4 {
            animation: slowFloat 25s infinite ease-in-out;
        }
        
        /* Glass card styles - enhanced glassy look */
        .glass-card {
            background: rgba(20, 24, 36, 0.75);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }
        
        .glass-card:hover {
            transform: translateY(-4px);
            background: rgba(26, 31, 45, 0.85);
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(15px);
        }
        
        /* Extra glassy effect with gradient border */
        .glass-card-glow {
            background: rgba(20, 24, 36, 0.7);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.1);
            position: relative;
            z-index: 2;
        }
        
        .glass-card-glow:hover {
            transform: translateY(-4px);
            border-color: rgba(79, 70, 229, 0.6);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.2);
        }
        
        /* Button styles - glassy */
        .btn-primary-glass {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.9), rgba(124, 58, 237, 0.9));
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            position: relative;
            z-index: 2;
        }
        
        .btn-primary-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.4);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.95), rgba(139, 92, 246, 0.95));
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .btn-outline-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #e0e0e0;
            position: relative;
            z-index: 2;
        }
        
        .btn-outline-glass:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Feature icons - glassy */
        .feature-icon {
            width: 56px;
            height: 56px;
            background: rgba(26, 31, 45, 0.8);
            backdrop-filter: blur(8px);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }
        
        .glass-card:hover .feature-icon {
            background: rgba(79, 70, 229, 0.2);
            border-color: rgba(79, 70, 229, 0.3);
            transform: scale(1.05);
        }
        
        /* Stats card */
        .stat-card {
            background: rgba(20, 24, 36, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff, #8b92b0);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        /* Admin link - concealed */
        .admin-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            opacity: 0.08;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        
        .admin-link:hover {
            opacity: 0.5;
        }
        
        .admin-link a {
            color: white;
            text-decoration: none;
            font-size: 11px;
            background: rgba(26, 31, 45, 0.9);
            backdrop-filter: blur(5px);
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Form controls glassy */
        .form-control-glass {
            background: rgba(26, 31, 45, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: white;
            border-radius: 12px;
            padding: 12px 16px;
        }
        
        .form-control-glass:focus {
            background: rgba(31, 37, 53, 0.9);
            border-color: #4f46e5;
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15);
        }
        
        .form-control-glass::placeholder {
            color: #5a6178;
        }
        
        .form-select-glass {
            background: rgba(26, 31, 45, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: white;
            border-radius: 12px;
            padding: 12px 16px;
        }
        
        .form-select-glass option {
            background: #0a0e1a;
        }
        
        /* Modal glassy */
        .modal-content-glass {
            background: rgba(15, 19, 34, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }
        
        /* Navbar glassy */
        .navbar-glass {
            background: rgba(15, 19, 34, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 16px 0;
            position: relative;
            z-index: 100;
        }
        
        /* Button group */
        .button-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 32px;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-up {
            animation: fadeInUp 0.8s ease-out;
        }
        
        /* Content wrapper to sit above shapes */
        .content-wrapper {
            position: relative;
            z-index: 2;
        }
        
        /* Section headers */
        .section-header {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .section-subtitle {
            color: #8b92b0;
            font-size: 14px;
        }
        
        /* Badge styles */
        .badge-organizer {
            background: #4f46e5;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        .badge-member {
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .section-title {
                font-size: 24px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn-primary-glass, .btn-outline-glass {
                width: 100%;
                text-align: center;
            }
            
            .shape-1, .shape-4 {
                width: 200px;
                height: 200px;
            }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #0a0e1a;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #2a2f3f;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #3a4055;
        }
        
        /* Hero section */
        .hero-section {
            padding: 80px 0;
            position: relative;
            z-index: 2;
        }
        
        /* Text colors */
        .text-muted-custom {
            color: #8b92b0;
        }
        
        hr {
            border-color: rgba(255, 255, 255, 0.05);
        }
        
        /* Floating particles */
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(79, 70, 229, 0.5);
            border-radius: 50%;
            animation: particleFloat 10s infinite linear;
        }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    
    <!-- Floating Shapes Background -->
    <div class="shapes-container">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
        <div class="shape shape-7"></div>
        <div class="shape shape-8"></div>
    </div>
    
    <!-- Navbar -->
    <nav class="navbar-glass fixed-top">
        <div class="container">
            <a class="navbar-brand text-white fw-bold fs-4" href="#">
                <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>WhistleGuard
            </a>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Hero Section -->
        <div class="hero-section" style="margin-top: 70px;">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-7 animate-fade-up">
                        <h1 class="display-3 fw-bold mb-4" style="background: linear-gradient(135deg, #ffffff, #8b92b0); -webkit-background-clip: text; background-clip: text; color: transparent;">
                            <i class="fas fa-whistle me-2" style="color: #4f46e5;"></i>Speak Truth.<br>Stay Anonymous.
                        </h1>
                        <p class="lead mb-4 text-muted-custom">
                            A secure, encrypted whistleblower platform that protects your identity while exposing wrongdoing. 
                            Your voice matters, and we keep it safe.
                        </p>
                        
                        <!-- Button Group -->
                        <div class="button-group">
                            <button class="btn-primary-glass" data-bs-toggle="modal" data-bs-target="#submitReportModal">
                                <i class="fas fa-pen-alt me-2"></i>Submit Anonymous Report
                            </button>
                            <button class="btn-primary-glass" data-bs-toggle="modal" data-bs-target="#trackReportModal" style="padding: 8px 20px;">
                                <i class="fas fa-search me-2"></i>Track
                            </button>
                            <!-- <a href="view-reports.php" class="btn-primary-glass" style="text-decoration: none; display: inline-block;">
                                <i class="fas fa-search me-2"></i>Track
                            </a> -->
                            <a href="dashboard.php" class="btn-primary-glass" style="text-decoration: none; display: inline-block;">
                                <i class="fas fa-chart-line me-2"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-5 d-none d-lg-block animate-fade-up">
                        <div class="glass-card-glow p-4 text-center">
                            <i class="fas fa-lock-shield fs-1 mb-3" style="color: #4f46e5;"></i>
                            <h5 class="fw-bold mb-2">End-to-End Encrypted</h5>
                            <p class="small text-muted-custom">Your identity is never stored. All communications are encrypted.</p>
                            <hr>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <div class="stat-number" id="reportsCount">0</div>
                                    <small class="text-muted-custom">Reports Filed</small>
                                </div>
                                <div class="col-6">
                                    <div class="stat-number" id="resolvedCount">0</div>
                                    <small class="text-muted-custom">Resolved Cases</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="container py-5" id="features">
            <div class="section-header text-center">
                <h2 class="section-title">Why Choose WhistleGuard?</h2>
                <p class="section-subtitle">Enterprise-grade security for whistleblowers worldwide</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="feature-icon">
                            <i class="fas fa-user-secret" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Complete Anonymity</h5>
                        <p class="small text-muted-custom mb-0">No IP logging, no tracking, no personal information required.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="feature-icon">
                            <i class="fas fa-lock" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">End-to-End Encryption</h5>
                        <p class="small text-muted-custom mb-0">All reports and files encrypted before storage.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="feature-icon">
                            <i class="fas fa-clock" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Self-Destruct Evidence</h5>
                        <p class="small text-muted-custom mb-0">Set expiration dates on sensitive files.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="feature-icon">
                            <i class="fas fa-comments" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Real-Time Messaging</h5>
                        <p class="small text-muted-custom mb-0">Secure chat with administrators.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="feature-icon">
                            <i class="fas fa-trash-alt" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Automatic Expiration</h5>
                        <p class="small text-muted-custom mb-0">Reports expire after specified timeframe.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card p-4 h-100">
                        <div class="feature-icon">
                            <i class="fas fa-shield-virus" style="color: #4f46e5;"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Tor Compatible</h5>
                        <p class="small text-muted-custom mb-0">Fully accessible via Tor Browser.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- How It Works Section -->
        <div class="container py-5" id="how-it-works">
            <div class="section-header text-center">
                <h2 class="section-title">How It Works</h2>
                <p class="section-subtitle">Simple, secure, and anonymous process</p>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="glass-card p-4 text-center h-100">
                        <div class="display-4 fw-bold mb-3" style="color: #4f46e5;">1</div>
                        <i class="fas fa-edit fs-1 mb-3" style="color: #4f46e5;"></i>
                        <h6 class="fw-bold">Submit Report</h6>
                        <p class="small text-muted-custom mb-0">Fill out the anonymous form</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card p-4 text-center h-100">
                        <div class="display-4 fw-bold mb-3" style="color: #4f46e5;">2</div>
                        <i class="fas fa-qrcode fs-1 mb-3" style="color: #4f46e5;"></i>
                        <h6 class="fw-bold">Get Tracking Code</h6>
                        <p class="small text-muted-custom mb-0">Receive unique tracking code</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card p-4 text-center h-100">
                        <div class="display-4 fw-bold mb-3" style="color: #4f46e5;">3</div>
                        <i class="fas fa-comment-dots fs-1 mb-3" style="color: #4f46e5;"></i>
                        <h6 class="fw-bold">Secure Communication</h6>
                        <p class="small text-muted-custom mb-0">Chat via encrypted channel</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="glass-card p-4 text-center h-100">
                        <div class="display-4 fw-bold mb-3" style="color: #4f46e5;">4</div>
                        <i class="fas fa-check-circle fs-1 mb-3" style="color: #4f46e5;"></i>
                        <h6 class="fw-bold">Resolution</h6>
                        <p class="small text-muted-custom mb-0">Case resolved, evidence self-destructs</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Section -->
        <div class="container py-5" id="security">
            <div class="glass-card-glow p-5">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <i class="fas fa-shield-haltered display-1 mb-3" style="color: #4f46e5;"></i>
                        <h3 class="fw-bold mb-3">Military-Grade Security</h3>
                        <p class="text-muted-custom mb-3">Your safety is our priority. We implement multiple layers of security.</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> No IP logging or tracking</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> AES-256 encryption for all data</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> Automatic data purging</li>
                            <li class="mb-2"><i class="fas fa-check-circle me-2" style="color: #10b981;"></i> Rate limiting anti-bruteforce</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-card p-4 text-center">
                            <i class="fas fa-fingerprint fs-1 mb-3" style="color: #4f46e5;"></i>
                            <h5 class="fw-bold">Your Identity Stays Hidden</h5>
                            <p class="small text-muted-custom mb-0">We never ask for your name, email, or any personal information.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="container py-5" id="faq">
            <div class="section-header text-center">
                <h2 class="section-title">Frequently Asked Questions</h2>
                <p class="section-subtitle">Got questions? We've got answers</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="fw-bold mb-2"><i class="fas fa-question-circle me-2" style="color: #4f46e5;"></i> Is this really anonymous?</h6>
                        <p class="small text-muted-custom mb-0">Yes! We don't track IP addresses, use cookies, or store any identifying information.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="fw-bold mb-2"><i class="fas fa-question-circle me-2" style="color: #4f46e5;"></i> How do I track my report?</h6>
                        <p class="small text-muted-custom mb-0">You'll receive a unique tracking code when you submit your report.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="fw-bold mb-2"><i class="fas fa-question-circle me-2" style="color: #4f46e5;"></i> Can I submit evidence anonymously?</h6>
                        <p class="small text-muted-custom mb-0">Absolutely. All files are encrypted and metadata is stripped.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="glass-card p-4">
                        <h6 class="fw-bold mb-2"><i class="fas fa-question-circle me-2" style="color: #4f46e5;"></i> What happens to my data?</h6>
                        <p class="small text-muted-custom mb-0">Data is automatically purged after your specified timeframe.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="container py-4">
            <div class="glass-card p-4 text-center">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-3 mb-md-0">
                        <i class="fas fa-shield-alt me-2" style="color: #4f46e5;"></i>WhistleGuard - Speaking Truth Safely
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted-custom">© 2026 WhistleGuard. All rights reserved.</small>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Concealed Admin Link -->
    <div class="admin-link">
        <a href="admin-login.php">
            <i class="fas fa-user-shield me-1"></i>Admin Access
        </a>
    </div>
    
    <!-- Submit Report Modal -->
    <!-- Replace the submitReportModal content with this -->
<div class="modal fade" id="submitReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2" style="color: #4f46e5;"></i>Submit Anonymous Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm" action="app/submit-report.php" method="POST" enctype="multipart/form-data" target="_blank">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select form-select-glass" name="category" required>
                            <option value="">Select category</option>
                            <option value="Corruption">Corruption</option>
                            <option value="Fraud">Fraud</option>
                            <option value="Harassment">Harassment</option>
                            <option value="Safety Violations">Safety Violations</option>
                            <option value="Data Breach">Data Breach</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control form-control-glass" name="description" rows="5" placeholder="Describe the incident in detail..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select class="form-select form-select-glass" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Evidence (Optional - Max 10MB)</label>
                        <input type="file" class="form-control form-control-glass" name="evidence" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                        <small class="text-muted-custom">Supported formats: JPG, PNG, PDF, DOC, DOCX</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="self_destruct" value="1" id="selfDestruct">
                        <label class="form-check-label" for="selfDestruct">
                            Enable self-destruct after 30 days
                        </label>
                    </div>
                    <button type="submit" class="btn-primary-glass w-100">
                        <i class="fas fa-paper-plane me-2"></i>Submit Report
                    </button>
                </form>
                <div class="text-center mt-3">
                    <small class="text-muted-custom">
                        <i class="fas fa-lock me-1"></i>Your report is encrypted and anonymous
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <!-- Track Report Modal -->
    <div class="modal fade" id="trackReportModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        <i class="fas fa-search me-2" style="color: #4f46e5;"></i>Track Your Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="trackForm" action="app/track-report.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Tracking Code</label>
                            <input type="text" name="tracking_code" class="form-control form-control-glass" placeholder="Enter your tracking code" required>
                            <small class="text-muted-custom">Example: ABC123XYZ</small>
                        </div>
                        <button type="submit" class="btn-primary-glass w-100">
                            <i class="fas fa-search me-2"></i>Track Report
                        </button>
                    </form>
                    <hr>
                    <div class="text-center">
                        <p class="small text-muted-custom mb-0">Lost your tracking code?</p>
                        <a href="#" class="small" style="color: #4f46e5;">Contact support with your anonymous session ID</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Animate stats counter
        function animateCounter(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.innerText = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
        
        // Simulate stats
        setTimeout(() => {
            const reportsElement = document.getElementById('reportsCount');
            const resolvedElement = document.getElementById('resolvedCount');
            if (reportsElement) animateCounter(reportsElement, 0, <?=$num_reports ?>, 2000);
            if (resolvedElement) animateCounter(resolvedElement, 0,  <?=$num_resolved_reports ?>, 2000);
        }, 500);
        
        // Create floating particles
        function createParticles() {
            const container = document.querySelector('.shapes-container');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 3 + 1 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = Math.random() * 15 + 8 + 's';
                particle.style.opacity = Math.random() * 0.5;
                container.appendChild(particle);
            }
        }
        
        createParticles();
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Console warning
        console.log('%c🔒 SECURITY NOTICE: This is a secure whistleblower platform. Your anonymity is protected.', 'color: #4f46e5; font-size: 14px; font-weight: bold;');
    </script>
</body>
</html>