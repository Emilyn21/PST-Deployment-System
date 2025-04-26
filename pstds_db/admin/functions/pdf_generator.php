<?php
require('../../fpdf186/fpdf.php'); // Adjust if needed
date_default_timezone_set("Asia/Manila"); // Set timezone

function generatePDF($tableData, $filename = "List_of_Programs.pdf") {
    $pdf = new FPDF('P', 'in', 'A4'); // Portrait, Inches, A4
    $pdf->SetMargins(1, 0.38, 1); // Left: 1 in, Top: 0.38 in, Right: 1 in
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 11);

    // University Header
    $pdf->Image('../../assets/img/cvsu-logo-header.jpg', 1, 0.38, 0.8);
    $pdf->Cell(0, 0.3, "Republic of the Philippines", 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 0.3, "CAVITE STATE UNIVERSITY", 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 0.3, "Don Severino de las Alas Campus", 0, 1, 'C');
    $pdf->Cell(0, 0.3, "Indang, Cavite", 0, 1, 'C');
    $pdf->Ln(0.3);

    // Title
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 0.3, "List of Programs", 0, 1, 'C');
    $pdf->Ln(0.2);

    // Get available page width
    $pageWidth = $pdf->GetPageWidth() - $pdf->lMargin - $pdf->rMargin; // Subtract left & right margins

    // Define relative width proportions (modify as needed)
    $colProportions = [0.3, 0.15, 0.4, 0.15]; // Should sum to 1.0

    // Calculate dynamic column widths
    $colWidths = array_map(fn($p) => $p * $pageWidth, $colProportions);

    // Table Headers
    $pdf->SetFont('Arial', 'B', 11);
    $headers = ["Program Name", "Abbreviation", "Description", "Status"];
    foreach ($headers as $i => $header) {
        $pdf->Cell($colWidths[$i], 0.4, $header, 1, 0, 'C');
    }
    $pdf->Ln();

    // Table Data
    $pdf->SetFont('Arial', '', 10);
    foreach ($tableData as $row) {
        $yStart = $pdf->GetY(); // Store Y position before adding MultiCell

        // Program Name
        $pdf->Cell($colWidths[0], 0.4, utf8_decode($row[0]), 1, 0, 'C');

        // Abbreviation
        $pdf->Cell($colWidths[1], 0.4, utf8_decode($row[1]), 1, 0, 'C');

        // Description (auto-wrap)
        $x = $pdf->GetX();
        $pdf->MultiCell($colWidths[2], 0.4, utf8_decode($row[2]), 1, 'L');
        $yEnd = $pdf->GetY(); // Get updated Y position

        // Move back for Status column
        $pdf->SetXY($x + $colWidths[2], $yStart);
        $pdf->Cell($colWidths[3], ($yEnd - $yStart), utf8_decode($row[3]), 1, 1, 'C'); // Match height dynamically
    }

    // Prepared by section
    $pdf->Ln(0.5);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 0.3, "Prepared by:", 0, 1, 'L');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 0.3, "Johnbert C. Tagle", 0, 1, 'L'); // Replace with dynamic name

    // Date and time footer
    $pdf->Ln(0.5);
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 0.2, "Date and Time Generated: " . date("F d, Y - h:i A"), 0, 1, 'R');

    // Output the PDF
    $pdf->Output("D", $filename); // Force download
}
?>

