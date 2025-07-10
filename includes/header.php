<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHIC AFFILIATE</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #2196F3;
            --secondary-color: #64B5F6;
            --accent-color: #1976D2;
            --background-color: #F5F5F5;
            --card-background: rgba(255, 255, 255, 0.9);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--background-color) 0%, #FFFFFF 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin-left: 280px;
            padding: 0 1rem 0;
            min-height: 100vh;
        }

        .orders-container {
            background: var(--card-background);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: none !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--accent-color) !important;
            color: white !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content"> 