<?php
require_once 'includes_auth.php';

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_data = $auth->getUserData($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ Verso - Sua plataforma de quadrinhos online</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            min-height: 100vh;
            padding: 0;
            margin: 0;
        }
        
        /* Modo Claro */
        body.light-mode {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #e94560;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Container de Busca */
        .search-container {
            position: relative;
        }

        .search-bar {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 8px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        body.light-mode .search-bar {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        
        .search-bar input {
            background: transparent;
            border: none;
            color: white;
            padding: 5px 10px;
            width: 300px;
            outline: none;
        }
        
        body.light-mode .search-bar input {
            color: #333;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        body.light-mode .search-bar input::placeholder {
            color: #718096;
        }
        
        .search-bar button {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            padding: 5px;
        }
        
        body.light-mode .search-bar button {
            color: #718096;
        }

        /* Botão de Busca Avançada no Header */
        .advanced-search-btn {
            padding: 8px 15px;
            font-size: 0.8rem;
            white-space: nowrap;
            text-decoration: none;
        }
        
        .user-avatar {
            position: relative;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e94560;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: rgba(26, 26, 46, 0.95);
            border-radius: 8px;
            padding: 10px 0;
            min-width: 200px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        body.light-mode .user-dropdown {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        
        body.light-mode .user-dropdown a {
            color: #333;
        }
        
        .user-dropdown a:hover {
            background: rgba(233, 69, 96, 0.2);
        }
        
        .user-avatar:hover .user-dropdown {
            display: block;
        }
        
        /* Modal de Busca */
        .search-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .search-modal.active {
            display: block;
            opacity: 1;
        }

        .search-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(26, 26, 46, 0.95);
        }

        .search-modal-header h3 {
            color: #e94560;
            margin: 0;
            font-size: 1.5rem;
        }

        .close-search {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .close-search:hover {
            background: rgba(233, 69, 96, 0.2);
        }

        .search-modal-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        /* Estatísticas e Abas */
        .search-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        #resultsCount {
            font-size: 1rem;
            opacity: 0.8;
        }

        .search-tabs {
            display: flex;
            gap: 10px;
        }

        .tab-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            background: #e94560;
            border-color: #e94560;
        }

        .tab-btn:hover:not(.active) {
            background: rgba(233, 69, 96, 0.3);
        }

        /* Container de Resultados */
        .search-results-container {
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .results-section {
            display: none;
            height: 100%;
            overflow-y: auto;
        }

        .results-section.active {
            display: block;
        }

        /* Grid de Resultados */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 10px 0;
        }

        /* Estados Vazios */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.5;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #e94560;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }

        /* Cards no Modal */
        .search-comic-card, .search-user-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .search-comic-card:hover, .search-user-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
            border-color: rgba(233, 69, 96, 0.3);
        }

        .search-comic-card {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .search-user-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
        }

        .search-comic-cover {
            width: 80px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .search-comic-info {
            flex: 1;
        }

        .search-comic-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 1rem;
            color: white;
        }

        .search-comic-meta {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 3px;
        }

        .search-comic-rating {
            color: #ffd700;
            font-size: 0.8rem;
            margin-top: 5px;
        }

        .search-user-card {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e94560 0%, #d8345f 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .search-user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .search-user-info {
            flex: 1;
        }

        .search-user-name {
            font-weight: 600;
            margin-bottom: 3px;
            color: white;
        }

        .search-user-email {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-bottom: 5px;
        }

        .search-user-stats {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .search-user-role {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(233, 69, 96, 0.3);
            border-radius: 10px;
            font-size: 0.7rem;
            margin-top: 5px;
        }

        /* Botão de seguir no modal */
        .search-user-actions {
            margin-top: 10px;
        }

        .btn-follow {
            padding: 6px 12px;
            border: 1px solid #e94560;
            background: transparent;
            color: #e94560;
            border-radius: 15px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-follow:hover {
            background: rgba(233, 69, 96, 0.1);
        }

        .btn-follow.following {
            background: #e94560;
            color: white;
        }

        .btn-follow.following:hover {
            background: #d8345f;
        }

        /* Footer da Busca */
        .search-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 20px;
        }

        .advanced-search-link {
            color: #e94560;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .advanced-search-link:hover {
            color: #d8345f;
        }

        .search-tips {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Loading State */
        .search-loading {
            text-align: center;
            padding: 40px;
        }

        .search-loading i {
            font-size: 2rem;
            color: #e94560;
            margin-bottom: 15px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modo Claro para o Modal */
        body.light-mode .search-modal {
            background: rgba(255, 255, 255, 0.95);
        }

        body.light-mode .search-modal-header {
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid #e2e8f0;
        }

        body.light-mode .search-modal-header h3 {
            color: #e94560;
        }

        body.light-mode .close-search {
            color: #333;
        }

        body.light-mode .close-search:hover {
            background: rgba(233, 69, 96, 0.1);
        }

        body.light-mode .search-stats {
            border-bottom: 1px solid #e2e8f0;
        }

        body.light-mode .tab-btn {
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            color: #333;
        }

        body.light-mode .search-comic-card, 
        body.light-mode .search-user-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid #e2e8f0;
            color: #333;
        }

        body.light-mode .search-comic-card:hover,
        body.light-mode .search-user-card:hover {
            background: white;
            border-color: #e94560;
        }

        body.light-mode .search-comic-title,
        body.light-mode .search-user-name {
            color: #333;
        }

        body.light-mode .search-footer {
            border-top: 1px solid #e2e8f0;
        }
        
        .categories {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .category {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        body.light-mode .category {
            background: rgba(0, 0, 0, 0.05);
            color: #4a5568;
        }
        
        .category.active {
            background: #e94560;
            color: white;
        }
        
        .category:hover {
            background: rgba(233, 69, 96, 0.7);
        }
        
        .comic-counter {
            text-align: center;
            margin: 10px 0 20px 0;
            font-size: 14px;
            opacity: 0.8;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        body.light-mode .comic-counter {
            color: #4a5568;
        }
        
        .featured-comic {
            position: relative;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #0f3460 0%, #1a1a2e 100%);
            background-image: url('https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT_1OXqNiK7qA0H461zQkADUkI6EwQIiExA3w&s');
            background-size: cover;
            background-position: center;
        }
        
        .featured-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            padding: 40px;
            color: white;
        }
        
        .featured-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .featured-description {
            font-size: 1.1rem;
            margin-bottom: 20px;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .featured-actions {
            display: flex;
            gap: 15px;
        }
        
        .comics-section {
            margin-bottom: 50px;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        body.light-mode .section-title {
            color: #2d3748;
        }
        
        .comics-carousel {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            transition: opacity 0.3s ease;
        }
        
        .comic-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        body.light-mode .comic-card {
            background: rgba(255, 255, 255, 0.8);
            color: #333;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .comic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        body.light-mode .comic-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .comic-cover {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }
        
        .comic-info {
            padding: 15px;
        }
        
        body.light-mode .comic-info {
            color: #333;
        }
        
        .comic-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        
        .comic-meta {
            font-size: 0.8rem;
            opacity: 0.7;
        }
        
        body.light-mode .comic-meta {
            color: #718096;
        }
        
        .card-actions {
            padding: 0 15px 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border: none;
            font-size: 0.9rem;
            text-align: center;
        }
        
        .btn-primary {
            background: #e94560;
            color: white;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #d8345f;
        }
        
        .btn-play {
            background: #e94560;
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid #e94560;
            color: #e94560;
        }
        
        body.light-mode .btn-outline {
            color: #e94560;
        }
        
        .btn-outline:hover {
            background: rgba(233, 69, 96, 0.1);
        }
        
        .progress {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        body.light-mode .progress {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .progress-bar {
            height: 100%;
            background: #e94560;
            transition: width 0.3s ease;
        }
        
        .continue-row .comics-carousel {
            display: flex;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 10px;
        }
        
        .continue-row .comic-card {
            min-width: 200px;
            flex-shrink: 0;
        }
        
        .browser-info-link {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(15, 52, 96, 0.8);
            color: white;
            padding: 12px 15px;
            border-radius: 25px;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        body.light-mode .browser-info-link {
            background: rgba(233, 69, 96, 0.8);
            color: white;
        }
        
        .browser-info-link:hover {
            background: rgba(15, 52, 96, 1);
            transform: translateY(-2px);
        }
        
        body.light-mode .browser-info-link:hover {
            background: rgba(233, 69, 96, 1);
        }
        
        .theme-toggle {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: rgba(15, 52, 96, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
        }
        
        body.light-mode .theme-toggle {
            background: rgba(233, 69, 96, 0.8);
            color: white;
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .no-comics {
            text-align: center;
            padding: 40px;
            opacity: 0.7;
            font-style: italic;
        }
        
        /* Botão Voltar */
        .back-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: rgba(233, 69, 96, 0.3);
            transform: translateY(-2px);
        }
        
        body.light-mode .back-button {
            background: rgba(0, 0, 0, 0.05);
            color: #333;
            border: 1px solid #e2e8f0;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 15px;
            }
            
            .header-right {
                width: 100%;
                justify-content: space-between;
            }
            
            .search-bar input {
                width: 200px;
            }
            
            .advanced-search-btn {
                display: none;
            }

            .search-modal-content {
                padding: 10px;
            }
            
            .search-modal-header {
                padding: 15px 20px;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .search-stats {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .search-tabs {
                justify-content: center;
            }
            
            .search-footer {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .featured-comic {
                height: 300px;
            }
            
            .featured-title {
                font-size: 1.8rem;
            }
            
            .featured-description {
                font-size: 1rem;
            }
            
            .comics-carousel {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .comic-cover {
                height: 220px;
            }
        }

        /* Scrollbar personalizado */
        .continue-row .comics-carousel::-webkit-scrollbar {
            height: 8px;
        }
        
        .continue-row .comics-carousel::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        body.light-mode .continue-row .comics-carousel::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .continue-row .comics-carousel::-webkit-scrollbar-thumb {
            background: #e94560;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="container comics-page" id="comicsPage">
        <header>
            <div class="header-left">
                <a href="comics.php" class="logo">HQ VERSO</a>
            </div>
            <div class="header-right">
                <div class="search-container">
                    <div class="search-bar" id="searchBar">
                        <input type="text" placeholder="Buscar quadrinhos, usuários, autores..." aria-label="Buscar" id="searchInput">
                        <button type="button" aria-label="Buscar" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <!-- Modal de Busca -->
                    <div class="search-modal" id="searchModal">
                        <div class="search-modal-header">
                            <h3>Resultados da Busca</h3>
                            <button class="close-search" id="closeSearch">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="search-modal-content">
                            <div class="search-stats" id="searchStats">
                                <span id="resultsCount">Digite para buscar</span>
                                <div class="search-tabs" id="searchTabs">
                                    <button class="tab-btn active" data-tab="all">Todos</button>
                                    <button class="tab-btn" data-tab="comics">Quadrinhos</button>
                                    <button class="tab-btn" data-tab="users">Usuários</button>
                                </div>
                            </div>
                            
                            <div class="search-results-container">
                                <div class="results-section active" id="resultsAll">
                                    <div class="empty-state" id="emptyAll">
                                        <i class="fas fa-search"></i>
                                        <p>Digite algo para buscar quadrinhos e usuários</p>
                                    </div>
                                    <div class="results-grid" id="resultsGridAll"></div>
                                </div>
                                
                                <div class="results-section" id="resultsComics">
                                    <div class="empty-state" id="emptyComics">
                                        <i class="fas fa-book"></i>
                                        <p>Nenhum quadrinho encontrado</p>
                                    </div>
                                    <div class="results-grid" id="resultsGridComics"></div>
                                </div>
                                
                                <div class="results-section" id="resultsUsers">
                                    <div class="empty-state" id="emptyUsers">
                                        <i class="fas fa-users"></i>
                                        <p>Nenhum usuário encontrado</p>
                                    </div>
                                    <div class="results-grid" id="resultsGridUsers"></div>
                                </div>
                            </div>
                            
                            <div class="search-footer">
                                <div class="search-tips">
                                    <strong>Dica:</strong> Tente buscar por título, autor, editora ou nome de usuário
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="user-avatar" id="userAvatar">
                    <?php 
                    $user_data = $auth->getUserData($_SESSION['user_id']);
                    $initial = strtoupper(substr($user_data['username'], 0, 1));
                    
                    if($user_data['avatar'] && file_exists($user_data['avatar'])): 
                    ?>
                        <img src="<?php echo $user_data['avatar']; ?>" alt="Avatar" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    <?php else: ?>
                        <span class="avatar-letter"><?php echo $initial; ?></span>
                    <?php endif; ?>
                    
                    <div class="user-dropdown">
                        <a href="perfil.php"><i class="fas fa-user"></i> Meu Perfil</a>
                        <?php if($auth->isAdmin()): ?>
                            <a href="admin.php"><i class="fas fa-cog"></i> Painel Admin</a>
                        <?php endif; ?>
                        <a href="detection.html"><i class="fas fa-info-circle"></i> Info do Navegador</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="categories">
            <div class="category active" data-category="all">Todos</div> 
            <div class="category" data-category="super-herois">Super-heróis</div>
            <div class="category" data-category="manga">Mangá</div>
            <div class="category" data-category="graphic-novels">Graphic Novels</div>
            <div class="category" data-category="classicos">Clássicos</div>
            <div class="category" data-category="indie">Indie</div>
            <div class="category" data-category="favoritos">Favoritos</div>
        </div>
        
        <div class="comic-counter" id="comicCounter">Carregando quadrinhos...</div>
        
        <div class="featured-comic">
            <div class="featured-overlay">
                <h2 class="featured-title">Batman: O Cavaleiro das Trevas</h2>
                <p class="featured-description">A obra-prima de Frank Miller que redefiniu o Batman para sempre.</p>
                <div class="featured-actions">
                    <button class="btn btn-play" onclick="window.location.href='leitor.html?comic=batman&title=Batman: O Cavaleiro das Trevas'">
                        <i class="fas fa-play"></i> Ler agora
                    </button>
                    <button class="btn btn-outline">
                        <i class="fas fa-plus"></i> Minha lista
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Seção Continuar Lendo -->
        <div class="comics-section continue-row">
            <h3 class="section-title">Continuar Lendo</h3>
            <div class="comics-carousel" id="continueReading">
                <div class="no-comics">Nenhum quadrinho em progresso</div>
            </div>
        </div>
        
        <!-- Seção Recomendados -->
        <div class="comics-section">
            <h3 class="section-title">Recomendados para você</h3>
            <div class="comics-carousel" id="recommendedComics">
                <div class="no-comics">Carregando recomendações...</div>
            </div>
        </div>
        
        <!-- Seção Clássicos da DC -->
        <div class="comics-section">
            <h3 class="section-title">Clássicos da DC</h3>
            <div class="comics-carousel" id="dcClassics">
                <div class="no-comics">Carregando clássicos...</div>
            </div>
        </div>
        
        <!-- Seção Mangás Populares -->
        <div class="comics-section">
            <h3 class="section-title">Mangás Populares</h3>
            <div class="comics-carousel" id="popularManga">
                <div class="no-comics">Carregando mangás...</div>
            </div>
        </div>
        
        <!-- Seção Graphic Novels -->
        <div class="comics-section">
            <h3 class="section-title">Graphic Novels</h3>
            <div class="comics-carousel" id="graphicNovels">
                <div class="no-comics">Carregando graphic novels...</div>
            </div>
        </div>
    </div>

    <a href="detection.html" class="browser-info-link">
        <i class="fas fa-info-circle"></i> Info Navegador
    </a>

    <script>
        // Dados completos dos quadrinhos
        const comicsData = {
            "all": [
                {
                    id: 1,
                    title: "Homem-Aranha: A Última Caçada",
                    cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTg9az9jtGfQoj20u_4hUEeTQXvXvX9i0W2KPKw&s",
                    meta: "Capítulo 3",
                    progress: 20,
                    categories: ["super-herois", "classicos"],
                    description: "A clássica história onde o Homem-Aranha enfrenta seu maior desafio."
                },
                {
                    id: 2,
                    title: "Watchmen",
                    cover: "https://upload.wikimedia.org/wikipedia/pt/d/d0/Watchmen.jpg",
                    meta: "Página 45",
                    progress: 45,
                    categories: ["super-herois", "graphic-novels", "classicos"],
                    description: "A revolucionária graphic novel que questiona a natureza dos super-heróis."
                },
                {
                    id: 3,
                    title: "Sandman: Prelúdios e Noturnos",
                    cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcReNzUJRFsrqKb-Y4UIxg-jUSPYg-4ermAT3w&s",
                    meta: "Volume 1",
                    progress: 60,
                    categories: ["graphic-novels", "classicos"],
                    description: "A primeira coleção da aclamada série de Neil Gaiman."
                },
                {
                    id: 4,
                    title: "V de Vingança",
                    cover: "https://m.media-amazon.com/images/I/711dLCQ6kuL._UF1000,1000_QL80_.jpg",
                    meta: "50% lido",
                    progress: 50,
                    categories: ["graphic-novels", "classicos"],
                    description: "A distópica graphic novel sobre anarquia e liberdade."
                },
                {
                    id: 5,
                    title: "Maus",
                    cover: "https://m.media-amazon.com/images/I/916IgqQ-54L.jpg",
                    meta: "Art Spiegelman",
                    categories: ["graphic-novels", "classicos"],
                    description: "A premiada graphic novel sobre o Holocausto."
                },
                {
                    id: 6,
                    title: "Persépolis",
                    cover: "https://m.media-amazon.com/images/I/814zhAWOKBL._UF1000,1000_QL80_.jpg",
                    meta: "Marjane Satrapi",
                    categories: ["graphic-novels", "classicos"],
                    description: "A autobiografia em quadrinhos sobre o Irã revolucionário."
                },
                {
                    id: 7,
                    title: "Hellboy: Caçada Selvagem",
                    cover: "https://images.tcdn.com.br/img/img_prod/1119494/hellboy_omnibus_vol_3_1709745_1_a6e86cfa8f53f219f4d0d0b0c4f79558.jpg",
                    meta: "Mike Mignola",
                    categories: ["super-herois", "indie"],
                    description: "As primeiras aventuras do demônio herói."
                },
                {
                    id: 8,
                    title: "Saga",
                    cover: "https://m.media-amazon.com/images/I/81s49EEptML.jpg",
                    meta: "Brian K. Vaughan",
                    categories: ["graphic-novels", "indie"],
                    description: "A épica space opera de ficção científica."
                },
                {
                    id: 9,
                    title: "Superman: Terra Um",
                    cover: "https://m.media-amazon.com/images/I/91wpPruCKrL._UF1000,1000_QL80_.jpg",
                    meta: "2010",
                    categories: ["super-herois", "classicos"],
                    description: "Uma reinterpretação moderna do Homem de Aço."
                },
                {
                    id: 10,
                    title: "Liga da Justiça: A Torre de Babel",
                    cover: "https://super.abril.com.br/wp-content/uploads/2018/07/torredebabel.jpg",
                    meta: "2000",
                    categories: ["super-herois", "classicos"],
                    description: "Quando Batman se torna a maior ameaça da Liga."
                },
                {
                    id: 11,
                    title: "Akira",
                    cover: "https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg",
                    meta: "Katsuhiro Otomo",
                    categories: ["manga", "classicos"],
                    description: "A épica cyberpunk que revolucionou os mangás."
                },
                {
                    id: 12,
                    title: "Death Note",
                    cover: "https://m.media-amazon.com/images/I/81MZ6eFQsfL.jpg",
                    meta: "Tsugumi Ohba",
                    categories: ["manga"],
                    description: "Um estudante genius encontra um caderno que pode matar pessoas."
                },
                {
                    id: 13,
                    title: "Attack on Titan",
                    cover: "https://m.media-amazon.com/images/I/81d6e+kN5+L.jpg",
                    meta: "Hajime Isayama",
                    categories: ["manga"],
                    description: "Humanidade luta pela sobrevivência contra titãs gigantes."
                },
                {
                    id: 14,
                    title: "One-Punch Man",
                    cover: "https://m.media-amazon.com/images/I/81I1+-+0R0L.jpg",
                    meta: "ONE",
                    categories: ["manga", "super-herois"],
                    description: "Um herói tão forte que derrota qualquer inimigo com um só soco."
                },
                {
                    id: 15,
                    title: "Scott Pilgrim",
                    cover: "https://m.media-amazon.com/images/I/81K1+Z+Yf+L.jpg",
                    meta: "Bryan Lee O'Malley",
                    categories: ["indie", "graphic-novels"],
                    description: "Um baixista deve derrotar os 7 ex-namorados malvados de sua amada."
                },
                {
                    id: 16,
                    title: "Batman: Ano Um",
                    cover: "https://m.media-amazon.com/images/I/81zK5OjR5aL.jpg",
                    meta: "Frank Miller",
                    categories: ["super-herois", "classicos"],
                    description: "A origem definitiva do Cavaleiro das Trevas."
                },
                {
                    id: 17,
                    title: "X-Men: Fênix Negra",
                    cover: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSmC8oS-dR6n1EW5YxEnvL0mPaz13taAKsbvQ&s",
                    meta: "Chris Claremont",
                    categories: ["super-herois", "classicos"],
                    description: "A épica saga onde Jean Grey se torna a Fênix Negra."
                },
                {
                    id: 18,
                    title: "Monstress",
                    cover: "https://m.media-amazon.com/images/I/81bGs636lzL.jpg",
                    meta: "Marjorie Liu",
                    categories: ["graphic-novels", "indie"],
                    description: "Fantasia sombria em um mundo de guerra e monstros."
                }
            ],
            "super-herois": [1, 2, 7, 9, 10, 14, 16, 17],
            "manga": [11, 12, 13, 14],
            "graphic-novels": [2, 3, 4, 5, 6, 8, 15, 18],
            "classicos": [1, 2, 3, 4, 5, 6, 9, 10, 11, 16, 17],
            "indie": [7, 8, 15, 18],
            "favoritos": [1, 3, 5, 8, 11, 14, 16]
        };

        // Dados de usuários para busca
        const usersData = [
            {
                id: 1,
                username: "comic_lover",
                email: "comic.lover@email.com",
                role: "Leitor",
                comics_count: 42,
                favorites_count: 15,
                followers_count: 28,
                avatar: ""
            },
            {
                id: 2,
                username: "manga_fan",
                email: "manga.fan@email.com",
                role: "Leitor",
                comics_count: 28,
                favorites_count: 8,
                followers_count: 15,
                avatar: ""
            },
            {
                id: 3,
                username: "art_creator",
                email: "art.creator@email.com",
                role: "Artista",
                comics_count: 12,
                favorites_count: 32,
                followers_count: 45,
                avatar: ""
            },
            {
                id: 4,
                username: "dc_collector",
                email: "dc.collector@email.com",
                role: "Colecionador",
                comics_count: 67,
                favorites_count: 23,
                followers_count: 32,
                avatar: ""
            },
            {
                id: 5,
                username: "marvel_fan",
                email: "marvel.fan@email.com",
                role: "Fã",
                comics_count: 35,
                favorites_count: 18,
                followers_count: 21,
                avatar: ""
            }
        ];

        // Função para criar card de quadrinho
        function createComicCard(comic, showProgress = false) {
            const placeholder = `https://via.placeholder.com/200x300/1a1a2e/e94560?text=${encodeURIComponent(comic.title.substring(0, 15))}`;
            
            return `
                <div class="comic-card" data-id="${comic.id}" data-categories="${comic.categories.join(',')}">
                    <img src="${comic.cover}" 
                         alt="Capa do quadrinho ${comic.title}" 
                         class="comic-cover"
                         onerror="this.src='${placeholder}'">
                    <div class="comic-info">
                        <div class="comic-title">${comic.title}</div>
                        <div class="comic-meta">${comic.meta}</div>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-primary read-now" 
                                onclick="window.location.href='leitor.html?comic=${comic.id}&title=${encodeURIComponent(comic.title)}&progress=${comic.progress || 0}'">
                            Ler agora
                        </button>
                    </div>
                    ${showProgress && comic.progress ? `
                    <div class="progress"><div class="progress-bar" style="width:${comic.progress}%"></div></div>
                    ` : ''}
                </div>
            `;
        }

        // Função para criar card de quadrinho do banco de dados
        function createModalComicCard(comic) {
            const placeholder = `https://via.placeholder.com/80x120/1a1a2e/e94560?text=${encodeURIComponent(comic.title.substring(0, 10))}`;
            
            return `
                <div class="search-comic-card" data-id="${comic.id}" data-type="comic">
                    <img src="${comic.cover}" 
                         alt="${comic.title}" 
                         class="search-comic-cover"
                         onerror="this.src='${placeholder}'">
                    <div class="search-comic-info">
                        <div class="search-comic-title">${comic.title}</div>
                        <div class="search-comic-meta">${comic.meta}</div>
                        <div class="search-comic-meta">${comic.categories.join(', ')}</div>
                    </div>
                </div>
            `;
        }

        // Função para criar card de usuário no modal
function createModalUserCard(user, currentUserId = null) {
    const initial = user.username ? user.username.charAt(0).toUpperCase() : 'U';
    const isFollowing = user.is_following == 1; // Agora vem do banco como 0 ou 1
    const canFollow = currentUserId && currentUserId != user.id;
    
    return `
        <div class="search-user-card" data-id="${user.id}" data-type="user">
            <div class="search-user-avatar">
                ${user.avatar ? `<img src="${user.avatar}" alt="${user.username}">` : `<span>${initial}</span>`}
            </div>
            <div class="search-user-info">
                <div class="search-user-name">${user.username}</div>
                <div class="search-user-email">${user.email}</div>
                <div class="search-user-stats">
                    ${user.comics_count || 0} comics • ${user.followers_count || 0} seguidores
                </div>
                <div class="search-user-role">${user.role}</div>
                ${canFollow ? `
                <div class="search-user-actions">
                    <button class="btn-follow ${isFollowing ? 'following' : ''}" 
                            data-user-id="${user.id}"
                            onclick="toggleFollow(${user.id}, this)">
                        ${isFollowing ? '<i class="fas fa-user-check"></i> Seguindo' : '<i class="fas fa-user-plus"></i> Seguir'}
                    </button>
                </div>
                ` : ''}
            </div>
        </div>
    `;
}

        // Sistema de Busca Modal
        function setupModalSearch() {
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const searchModal = document.getElementById('searchModal');
            const closeSearch = document.getElementById('closeSearch');
            const searchTabs = document.querySelectorAll('.tab-btn');
            const resultsSections = document.querySelectorAll('.results-section');
            
            let currentResults = { comics: [], users: [] };
            let searchTimeout = null;
            
            // Abrir modal ao clicar no botão de busca ou no input
            searchButton.addEventListener('click', openSearchModal);
            searchInput.addEventListener('click', openSearchModal);
            
            // Fechar modal
            closeSearch.addEventListener('click', closeSearchModal);
            
            // Fechar modal ao clicar fora
            searchModal.addEventListener('click', function(e) {
                if (e.target === searchModal) {
                    closeSearchModal();
                }
            });
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && searchModal.classList.contains('active')) {
                    closeSearchModal();
                }
            });
            
            // Input dentro do modal
            const modalInput = document.createElement('input');
            modalInput.type = 'text';
            modalInput.placeholder = 'Digite para buscar...';
            modalInput.style.width = '100%';
            modalInput.style.padding = '15px 20px';
            modalInput.style.fontSize = '16px';
            modalInput.style.background = 'rgba(255, 255, 255, 0.1)';
            modalInput.style.border = '2px solid #e94560';
            modalInput.style.borderRadius = '10px';
            modalInput.style.color = 'white';
            modalInput.style.outline = 'none';
            modalInput.style.marginBottom = '20px';
            
            // Adicionar input ao modal
            const searchStats = document.getElementById('searchStats');
            searchStats.parentNode.insertBefore(modalInput, searchStats);
            
            // Busca em tempo real no modal
            modalInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (searchTerm.length < 2) {
                    showEmptyState();
                    return;
                }
                
                // Mostrar loading
                showLoadingState();
                
                searchTimeout = setTimeout(() => {
                    performModalSearch(searchTerm);
                }, 500);
            });
            
            function openSearchModal() {
                searchModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                setTimeout(() => {
                    modalInput.focus();
                }, 100);
            }
            
            function closeSearchModal() {
                searchModal.classList.remove('active');
                document.body.style.overflow = '';
                modalInput.value = '';
                showEmptyState();
            }
            
            function performModalSearch(searchTerm) {
    // Buscar quadrinhos (mantém os dados mockados por enquanto)
    const comicResults = comicsData.all.filter(comic => 
        comic.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
        comic.meta.toLowerCase().includes(searchTerm.toLowerCase()) ||
        comic.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
        comic.categories.some(cat => cat.toLowerCase().includes(searchTerm.toLowerCase()))
    );
    
    // AGORA BUSCA USUÁRIOS REAIS DO BANCO DE DADOS
    fetch(`search_users.php?q=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(userResults => {
            currentResults = { comics: comicResults, users: userResults };
            displayModalResults(currentResults, searchTerm);
        })
        .catch(error => {
            console.error('Erro na busca:', error);
            // Fallback para dados mockados em caso de erro
            const userResults = usersData.map(user => ({
                ...user,
                is_following: false,
            })).filter(user =>
                user.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                user.role.toLowerCase().includes(searchTerm.toLowerCase())
            );
            
            currentResults = { comics: comicResults, users: userResults };
            displayModalResults(currentResults, searchTerm);
        });
}
            function displayModalResults(results, searchTerm) {
                const totalResults = results.comics.length + results.users.length;
                
                // Atualizar estatísticas
                document.getElementById('resultsCount').textContent = 
                    `${totalResults} resultados para "${searchTerm}"`;
                
                // Atualizar todas as abas
                updateResultsDisplay('all');
            }
            
            function updateResultsDisplay(activeTab) {
                const comicsGridAll = document.getElementById('resultsGridAll');
                const comicsGridComics = document.getElementById('resultsGridComics');
                const comicsGridUsers = document.getElementById('resultsGridUsers');
                
                const emptyAll = document.getElementById('emptyAll');
                const emptyComics = document.getElementById('emptyComics');
                const emptyUsers = document.getElementById('emptyUsers');
                
                // Limpar grids
                comicsGridAll.innerHTML = '';
                comicsGridComics.innerHTML = '';
                comicsGridUsers.innerHTML = '';
                
                // Mostrar/ocultar estados vazios
                emptyAll.style.display = currentResults.comics.length === 0 && currentResults.users.length === 0 ? 'block' : 'none';
                emptyComics.style.display = currentResults.comics.length === 0 ? 'block' : 'none';
                emptyUsers.style.display = currentResults.users.length === 0 ? 'block' : 'none';
                
                // Popular aba "Todos"
                if (currentResults.comics.length > 0) {
                    currentResults.comics.slice(0, 6).forEach(comic => {
                        comicsGridAll.innerHTML += createModalComicCard(comic);
                    });
                }
                
                if (currentResults.users.length > 0) {
                    currentResults.users.slice(0, 4).forEach(user => {
                        comicsGridAll.innerHTML += createModalUserCard(user, <?php echo $_SESSION['user_id']; ?>);
                    });
                }
                
                // Popular aba "Quadrinhos"
                if (currentResults.comics.length > 0) {
                    currentResults.comics.forEach(comic => {
                        comicsGridComics.innerHTML += createModalComicCard(comic);
                    });
                }
                
                // Popular aba "Usuários"
                if (currentResults.users.length > 0) {
                    currentResults.users.forEach(user => {
                        comicsGridUsers.innerHTML += createModalUserCard(user, <?php echo $_SESSION['user_id']; ?>);
                    });
                }
                
                // Adicionar event listeners para os cards
                addCardEventListeners();
            }
            
            function addCardEventListeners() {
                // Event listeners para cards de quadrinhos
                document.querySelectorAll('.search-comic-card').forEach(card => {
                    card.addEventListener('click', function() {
                        const comicId = this.getAttribute('data-id');
                        const comic = currentResults.comics.find(c => c.id == comicId);
                        if (comic) {
                            closeSearchModal();
                            window.location.href = `leitor.html?comic=${comicId}&title=${encodeURIComponent(comic.title)}`;
                        }
                    });
                });
                
                // Event listeners para cards de usuários
                document.querySelectorAll('.search-user-card').forEach(card => {
                    card.addEventListener('click', function() {
                        const userId = this.getAttribute('data-id');
                        closeSearchModal();
                        window.location.href = `perfil.php?user_id=${userId}`;
                    });
                });
            }
            
            function showEmptyState() {
                document.getElementById('resultsCount').textContent = 'Digite para buscar';
                document.querySelectorAll('.results-grid').forEach(grid => grid.innerHTML = '');
                document.querySelectorAll('.empty-state').forEach(empty => empty.style.display = 'block');
            }
            
            function showLoadingState() {
                document.getElementById('resultsCount').textContent = 'Buscando...';
                document.querySelectorAll('.results-grid').forEach(grid => {
                    grid.innerHTML = `
                        <div class="search-loading">
                            <i class="fas fa-spinner"></i>
                            <div>Carregando resultados...</div>
                        </div>
                    `;
                });
            }
            
            // Trocar abas
            searchTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    
                    // Atualizar aba ativa
                    searchTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Mostrar seção correspondente
                    resultsSections.forEach(section => {
                        section.classList.remove('active');
                        if (section.id === `results${tabName.charAt(0).toUpperCase() + tabName.slice(1)}`) {
                            section.classList.add('active');
                        }
                    });
                    
                    updateResultsDisplay(tabName);
                });
            });
        }

        // Função para seguir/deseguir usuário
        function toggleFollow(userId, button) {
            const isFollowing = button.classList.contains('following');
            
            fetch('follow_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `user_id=${userId}&action=${isFollowing ? 'unfollow' : 'follow'}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    if(isFollowing) {
                        button.classList.remove('following');
                        button.innerHTML = '<i class="fas fa-user-plus"></i> Seguir';
                    } else {
                        button.classList.add('following');
                        button.innerHTML = '<i class="fas fa-user-check"></i> Seguindo';
                    }
                } else {
                    alert(data.message || 'Erro ao processar solicitação');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar solicitação');
            });
        }

        function filterComicsByCategory(category) {
            const allComics = comicsData.all;
            let filteredComics = [];
            
            if (category === 'all') {
                filteredComics = allComics;
            } else if (category === 'favoritos') {
                const favoriteIds = comicsData.favoritos;
                filteredComics = allComics.filter(comic => favoriteIds.includes(comic.id));
            } else {
                const categoryIds = comicsData[category] || [];
                filteredComics = allComics.filter(comic => categoryIds.includes(comic.id));
            }
            
            return filteredComics;
        }

        function renderComicsInSection(sectionId, comics, showProgress = false) {
            const section = document.getElementById(sectionId);
            if (!section) return;
            
            if (comics.length === 0) {
                section.innerHTML = '<div class="no-comics">Nenhum quadrinho encontrado</div>';
                return;
            }
            
            section.innerHTML = '';
            
            comics.forEach(comic => {
                section.innerHTML += createComicCard(comic, showProgress);
            });
        }

        function updateAllSections(category) {
            const filteredComics = filterComicsByCategory(category);
            
            const continueReadingComics = filteredComics.filter(comic => comic.progress && comic.progress > 0);
            renderComicsInSection('continueReading', continueReadingComics.slice(0, 4), true);
            
            const recommendedComics = filteredComics.slice(0, 4);
            renderComicsInSection('recommendedComics', recommendedComics);
            
            const dcComics = filteredComics.filter(comic => 
                comic.categories.includes('super-herois') && 
                (comic.title.includes('Batman') || comic.title.includes('Superman') || comic.title.includes('Liga'))
            );
            renderComicsInSection('dcClassics', dcComics.slice(0, 3));
            
            const mangaComics = filteredComics.filter(comic => 
                comic.categories.includes('manga')
            );
            renderComicsInSection('popularManga', mangaComics.slice(0, 4));
            
            const graphicNovels = filteredComics.filter(comic => 
                comic.categories.includes('graphic-novels')
            );
            renderComicsInSection('graphicNovels', graphicNovels.slice(0, 4));
            
            updateComicCount(filteredComics.length);
        }

        function updateComicCount(count) {
            const counter = document.getElementById('comicCounter');
            if (counter) {
                counter.textContent = `${count} quadrinhos encontrados`;
            }
        }

        function setupCategoryFilter() {
            document.querySelectorAll('.category').forEach(category => {
                category.addEventListener('click', function() {
                    document.querySelectorAll('.category').forEach(cat => cat.classList.remove('active'));
                    this.classList.add('active');
                    
                    const categoryType = this.getAttribute('data-category');
                    
                    document.querySelectorAll('.comics-carousel').forEach(carousel => {
                        carousel.style.opacity = '0.7';
                        setTimeout(() => {
                            carousel.style.opacity = '1';
                        }, 300);
                    });
                    
                    updateAllSections(categoryType);
                });
            });
        }

        function setupTheme() {
            const themeToggle = document.getElementById('themeToggle');
            const body = document.body;
            
            const savedTheme = localStorage.getItem('hq-verso-theme');
            if (savedTheme === 'light') {
                body.classList.add('light-mode');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            }
            
            themeToggle.addEventListener('click', () => {
                body.classList.toggle('light-mode');
                
                if (body.classList.contains('light-mode')) {
                    themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
                    localStorage.setItem('hq-verso-theme', 'light');
                } else {
                    themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
                    localStorage.setItem('hq-verso-theme', 'dark');
                }
            });
        }

        function initializePage() {
            setupTheme();
            setupCategoryFilter();
            setupModalSearch();
            updateAllSections('all');
            
            console.log('Sistema de quadrinhos inicializado!');
        }

        document.addEventListener('DOMContentLoaded', initializePage);
    </script>
</body>
</html>
