<!-- g

function printQRCode() {
    const qrImg = document.querySelector('#qr-code img');
    if (!qrImg) return;
    
    // Get student data from the form
    const lrn = document.getElementById('student-id').value;
    const firstName = document.getElementById('first-name').value;
    const middleName = document.getElementById('middle-name').value;
    const lastName = document.getElementById('last-name').value;
    const fullName = `${lastName}, ${firstName} ${middleName}`;
    
    // Create a canvas to generate the ID card image
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // ID card dimensions (portrait - 2.125" x 3.375" at 300 DPI)
    const cardWidth = 638;   // 2.125 * 300
    const cardHeight = 1012; // 3.375 * 300
    canvas.width = cardWidth;
    canvas.height = cardHeight;
    
    // Create image object for QR code
    const qrImage = new Image();
    qrImage.crossOrigin = 'anonymous';
    
    qrImage.onload = function() {
        // Create gradient background
        const gradient = ctx.createLinearGradient(0, 0, 0, cardHeight);
        gradient.addColorStop(0, '#4b6cb7');
        gradient.addColorStop(1, '#182848');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, cardWidth, cardHeight);
        
        // Draw white card background with rounded corners and shadow
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(20, 20, cardWidth - 40, cardHeight - 40, 20);
        ctx.shadowColor = 'rgba(0, 0, 0, 0.3)';
        ctx.shadowBlur = 15;
        ctx.shadowOffsetX = 5;
        ctx.shadowOffsetY = 5;
        ctx.fill();
        ctx.shadowColor = 'transparent'; // Reset shadow
        
        // Draw border with rounded corners
        ctx.strokeStyle = '#1e3a8a';
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.roundRect(20, 20, cardWidth - 40, cardHeight - 40, 20);
        ctx.stroke();
        
        // Calculate QR code size and position (full width with 10px padding)
        const qrPadding = 10;
        const qrSize = cardWidth - 60 - (2 * qrPadding);
        const qrX = 30 + qrPadding;
        const qrY = 40;
        
        // Draw QR code
        ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
        
        // Set font for LRN (modern sans-serif)
        ctx.fillStyle = '#1f2937';
        ctx.font = 'bold 28px "Helvetica Neue", Arial, sans-serif';
        ctx.textAlign = 'center';
        
        // Draw LRN
        const lrnText = `LRN: ${lrn}`;
        const lrnY = qrY + qrSize + 50;
        ctx.fillText(lrnText, cardWidth / 2, lrnY);
        
        // Set font for Full Name (dynamic sizing)
        const nameLength = fullName.length;
        let fontSize = nameLength > 25 ? 22 : nameLength > 20 ? 26 : 30;
        ctx.font = `bold ${fontSize}px "Helvetica Neue", Arial, sans-serif`;
        
        // Handle long names by wrapping text
        const maxWidth = cardWidth - 80;
        const words = fullName.split(' ');
        let line = '';
        let lines = [];
        
        for (let i = 0; i < words.length; i++) {
            const testLine = line + words[i] + ' ';
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            
            if (testWidth > maxWidth && i > 0) {
                lines.push(line.trim());
                line = words[i] + ' ';
            } else {
                line = testLine;
            }
        }
        lines.push(line.trim());
        
        // Draw full name (centered) with subtle text shadow
        const nameStartY = lrnY + 40;
        const lineHeight = fontSize + 10;
        ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
        ctx.shadowBlur = 3;
        ctx.shadowOffsetX = 2;
        ctx.shadowOffsetY = 2;
        
        lines.forEach((line, index) => {
            ctx.fillText(line, cardWidth / 2, nameStartY + (index * lineHeight));
        });
        ctx.shadowColor = 'transparent'; // Reset shadow
        
        // Add footer text (e.g., school name)
        ctx.font = 'italic 20px "Helvetica Neue", Arial, sans-serif';
        ctx.fillStyle = '#4b5563';
        ctx.fillText('Sample University', cardWidth / 2, cardHeight - 50);
        
        // Convert canvas to blob and automatically download
        canvas.toBlob(function(blob) {
            const downloadLink = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            downloadLink.href = url;
            downloadLink.download = `QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            downloadLink.style.display = 'none';
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            URL.revokeObjectURL(url);
            
            alert(`QR ID card image generated and downloaded: QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`);
        }, 'image/png', 1.0);
    };
    
    qrImage.onerror = function() {
        alert('Error loading QR code image. Please make sure the QR code is generated first.');
    };
    
    // Load the QR image
    qrImage.src = qrImg.src;
}
 -->




<!-- cl -1 -->

<!-- function printQRCode() {
    const qrImg = document.querySelector('#qr-code img');
    if (!qrImg) return;
    
    // Get student data from the form
    const lrn = document.getElementById('student-id').value;
    const firstName = document.getElementById('first-name').value;
    const middleName = document.getElementById('middle-name').value;
    const lastName = document.getElementById('last-name').value;
    const fullName = `${lastName}, ${firstName} ${middleName}`;
    
    // Create a canvas to generate the ID card image
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // ID card dimensions (portrait - 2.125" x 3.375" at 300 DPI)
    const cardWidth = 638;   // 2.125 * 300
    const cardHeight = 1012; // 3.375 * 300
    canvas.width = cardWidth;
    canvas.height = cardHeight;
    
    // Create image object for QR code
    const qrImage = new Image();
    qrImage.crossOrigin = 'anonymous';
    
    qrImage.onload = function() {
        // Create gradient background
        const gradient = ctx.createLinearGradient(0, 0, 0, cardHeight);
        gradient.addColorStop(0, '#f8f9fa');
        gradient.addColorStop(0.3, '#ffffff');
        gradient.addColorStop(0.7, '#ffffff');
        gradient.addColorStop(1, '#e9ecef');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, cardWidth, cardHeight);
        
        // Add elegant border with rounded corners effect
        ctx.strokeStyle = '#2c3e50';
        ctx.lineWidth = 3;
        ctx.strokeRect(15, 15, cardWidth - 30, cardHeight - 30);
        
        // Add inner shadow effect
        ctx.strokeStyle = '#bdc3c7';
        ctx.lineWidth = 1;
        ctx.strokeRect(18, 18, cardWidth - 36, cardHeight - 36);
        
        // Add header section with subtle background
        const headerHeight = 80;
        const headerGradient = ctx.createLinearGradient(0, 20, 0, headerHeight + 20);
        headerGradient.addColorStop(0, '#3498db');
        headerGradient.addColorStop(1, '#2980b9');
        ctx.fillStyle = headerGradient;
        ctx.fillRect(25, 25, cardWidth - 50, headerHeight);
        
        // Add header text
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 28px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('STUDENT ID', cardWidth / 2, 75);
        
        // Calculate QR code size with 10px padding (QR occupies full width minus padding)
        const cardPadding = 25; // Card border padding
        const qrPadding = 10; // QR code padding
        const availableWidth = cardWidth - (cardPadding * 2) - (qrPadding * 2);
        const qrSize = availableWidth;
        const qrX = cardPadding + qrPadding;
        const qrY = headerHeight + 50;
        
        // Add QR code background with shadow effect
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillRect(qrX - 5, qrY - 5, qrSize + 10, qrSize + 10);
        
        // Add white background for QR code
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(qrX, qrY, qrSize, qrSize);
        
        // Draw QR code
        ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
        
        // Add decorative line below QR code
        const lineY = qrY + qrSize + 30;
        const lineGradient = ctx.createLinearGradient(50, lineY, cardWidth - 50, lineY);
        lineGradient.addColorStop(0, 'transparent');
        lineGradient.addColorStop(0.2, '#3498db');
        lineGradient.addColorStop(0.8, '#3498db');
        lineGradient.addColorStop(1, 'transparent');
        ctx.strokeStyle = lineGradient;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(50, lineY);
        ctx.lineTo(cardWidth - 50, lineY);
        ctx.stroke();
        
        // Set font for LRN with enhanced styling
        ctx.fillStyle = '#2c3e50';
        ctx.font = 'bold 32px Arial';
        ctx.textAlign = 'center';
        
        // Add LRN with subtle background
        const lrnText = `LRN: ${lrn}`;
        const lrnY = lineY + 60;
        
        // Add text shadow effect for LRN
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillText(lrnText, cardWidth / 2 + 2, lrnY + 2);
        ctx.fillStyle = '#2c3e50';
        ctx.fillText(lrnText, cardWidth / 2, lrnY);
        
        // Set font for Full Name with better styling
        const nameLength = fullName.length;
        let fontSize = nameLength > 30 ? 22 : nameLength > 25 ? 26 : nameLength > 20 ? 28 : 30;
        ctx.font = `bold ${fontSize}px Arial`;
        ctx.fillStyle = '#34495e';
        
        // Handle long names by wrapping text
        const maxWidth = cardWidth - 80;
        const words = fullName.split(' ');
        let line = '';
        let lines = [];
        
        for (let i = 0; i < words.length; i++) {
            const testLine = line + words[i] + ' ';
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            
            if (testWidth > maxWidth && i > 0) {
                lines.push(line.trim());
                line = words[i] + ' ';
            } else {
                line = testLine;
            }
        }
        lines.push(line.trim());
        
        // Draw full name with enhanced styling
        const nameStartY = lrnY + 70;
        const lineHeight = fontSize + 10;
        
        lines.forEach((line, index) => {
            // Add text shadow for name
            ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
            ctx.fillText(line, cardWidth / 2 + 1, nameStartY + (index * lineHeight) + 1);
            ctx.fillStyle = '#34495e';
            ctx.fillText(line, cardWidth / 2, nameStartY + (index * lineHeight));
        });
        
        // Add footer decoration
        const footerY = cardHeight - 60;
        ctx.strokeStyle = '#bdc3c7';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(80, footerY);
        ctx.lineTo(cardWidth - 80, footerY);
        ctx.stroke();
        
        // Add small decorative elements (dots)
        ctx.fillStyle = '#3498db';
        for (let i = 0; i < 5; i++) {
            const dotX = (cardWidth / 6) * (i + 1);
            ctx.beginPath();
            ctx.arc(dotX, footerY + 15, 3, 0, Math.PI * 2);
            ctx.fill();
        }
        
        // Convert canvas to blob and automatically download
        canvas.toBlob(function(blob) {
            // Create download link
            const downloadLink = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            // Set download attributes
            downloadLink.href = url;
            downloadLink.download = `QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            downloadLink.style.display = 'none';
            
            // Append to body, click, and remove
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Clean up the URL object
            URL.revokeObjectURL(url);
            
            // Show success message with enhanced styling
            const successMsg = `✅ QR ID card generated successfully!\nFile: QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            alert(successMsg);
        }, 'image/png', 1.0);
    };
    
    qrImage.onerror = function() {
        alert('❌ Error loading QR code image. Please make sure the QR code is generated first.');
    };
    
    // Load the QR image
    qrImage.src = qrImg.src;
} -->




<!-- g 2

function printQRCode() {
    const qrImg = document.querySelector('#qr-code img');
    if (!qrImg) return;
    
    // Get student data from the form
    const lrn = document.getElementById('student-id').value;
    const firstName = document.getElementById('first-name').value;
    const middleName = document.getElementById('middle-name').value;
    const lastName = document.getElementById('last-name').value;
    const fullName = `${lastName}, ${firstName} ${middleName}`;
    
    // Create a canvas to generate the ID card image
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // ID card dimensions (portrait - 2.125" x 3.375" at 300 DPI)
    const cardWidth = 638;   // 2.125 * 300
    const cardHeight = 1012; // 3.375 * 300
    canvas.width = cardWidth;
    canvas.height = cardHeight;
    
    // Create image object for QR code
    const qrImage = new Image();
    qrImage.crossOrigin = 'anonymous';
    
    qrImage.onload = function() {
        // Create smooth gradient background
        const gradient = ctx.createLinearGradient(0, 0, 0, cardHeight);
        gradient.addColorStop(0, '#e3f2fd');
        gradient.addColorStop(0.4, '#f5faff');
        gradient.addColorStop(0.6, '#f5faff');
        gradient.addColorStop(1, '#dbeafe');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, cardWidth, cardHeight);
        
        // Add subtle pattern overlay
        ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
        for (let i = 0; i < cardHeight; i += 20) {
            ctx.fillRect(0, i, cardWidth, 10);
        }
        
        // Draw card background with rounded corners and shadow
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(15, 15, cardWidth - 30, cardHeight - 30, 25);
        ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
        ctx.shadowBlur = 20;
        ctx.shadowOffsetX = 5;
        ctx.shadowOffsetY = 5;
        ctx.fill();
        ctx.shadowColor = 'transparent';
        
        // Add elegant double border
        ctx.strokeStyle = '#1e40af';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.roundRect(15, 15, cardWidth - 30, cardHeight - 30, 25);
        ctx.stroke();
        ctx.strokeStyle = '#60a5fa';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.roundRect(18, 18, cardWidth - 36, cardHeight - 36, 22);
        ctx.stroke();
        
        // Add header section with gradient
        const headerHeight = 90;
        const headerGradient = ctx.createLinearGradient(0, 20, 0, headerHeight + 20);
        headerGradient.addColorStop(0, '#2563eb');
        headerGradient.addColorStop(1, '#1e40af');
        ctx.fillStyle = headerGradient;
        ctx.beginPath();
        ctx.roundRect(25, 25, cardWidth - 50, headerHeight, 15);
        ctx.fill();
        
        // Add header text with elegant font
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 30px "Roboto", Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('STUDENT ID', cardWidth / 2, 75);
        
        // Calculate QR code size with 10px padding
        const cardPadding = 25;
        const qrPadding = 10;
        const availableWidth = cardWidth - (cardPadding * 2) - (qrPadding * 2);
        const qrSize = availableWidth;
        const qrX = cardPadding + qrPadding;
        const qrY = headerHeight + 60;
        
        // Add QR code background with subtle shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.08)';
        ctx.beginPath();
        ctx.roundRect(qrX - 5, qrY - 5, qrSize + 10, qrSize + 10, 10);
        ctx.fill();
        
        // Add white background for QR code
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(qrX, qrY, qrSize, qrSize, 8);
        ctx.fill();
        
        // Draw QR code
        ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
        
        // Add decorative line below QR code
        const lineY = qrY + qrSize + 40;
        const lineGradient = ctx.createLinearGradient(50, lineY, cardWidth - 50, lineY);
        lineGradient.addColorStop(0, 'transparent');
        lineGradient.addColorStop(0.2, '#2563eb');
        lineGradient.addColorStop(0.8, '#2563eb');
        lineGradient.addColorStop(1, 'transparent');
        ctx.strokeStyle = lineGradient;
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(50, lineY);
        ctx.lineTo(cardWidth - 50, lineY);
        ctx.stroke();
        
        // Set font for LRN with enhanced styling
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 32px "Roboto", Arial, sans-serif';
        ctx.textAlign = 'center';
        
        // Add LRN with subtle background and shadow
        const lrnText = `LRN: ${lrn}`;
        const lrnY = lineY + 60;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillText(lrnText, cardWidth / 2 + 2, lrnY + 2);
        ctx.fillStyle = '#1e293b';
        ctx.fillText(lrnText, cardWidth / 2, lrnY);
        
        // Set font for Full Name with dynamic sizing
        const nameLength = fullName.length;
        let fontSize = nameLength > 30 ? 22 : nameLength > 25 ? 26 : nameLength > 20 ? 28 : 30;
        ctx.font = `bold ${fontSize}px "Roboto", Arial, sans-serif`;
        ctx.fillStyle = '#1e293b';
        
        // Handle long names by wrapping text
        const maxWidth = cardWidth - 80;
        const words = fullName.split(' ');
        let line = '';
        let lines = [];
        
        for (let i = 0; i < words.length; i++) {
            const testLine = line + words[i] + ' ';
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            
            if (testWidth > maxWidth && i > 0) {
                lines.push(line.trim());
                line = words[i] + ' ';
            } else {
                line = testLine;
            }
        }
        lines.push(line.trim());
        
        // Draw full name with enhanced styling
        const nameStartY = lrnY + 70;
        const lineHeight = fontSize + 12;
        lines.forEach((line, index) => {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
            ctx.fillText(line, cardWidth / 2 + 1, nameStartY + (index * lineHeight) + 1);
            ctx.fillStyle = '#1e293b';
            ctx.fillText(line, cardWidth / 2, nameStartY + (index * lineHeight));
        });
        
        // Add footer decoration with school name
        const footerY = cardHeight - 60;
        ctx.fillStyle = '#2563eb';
        ctx.font = 'italic 22px "Roboto", Arial, sans-serif';
        ctx.fillText('Sample University', cardWidth / 2, footerY);
        
        // Add decorative elements (small circles)
        ctx.fillStyle = '#60a5fa';
        for (let i = 0; i < 5; i++) {
            const dotX = (cardWidth / 6) * (i + 1);
            ctx.beginPath();
            ctx.arc(dotX, footerY + 20, 4, 0, Math.PI * 2);
            ctx.fill();
        }
        
        // Convert canvas to blob and automatically download
        canvas.toBlob(function(blob) {
            const downloadLink = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            downloadLink.href = url;
            downloadLink.download = `QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            downloadLink.style.display = 'none';
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            URL.revokeObjectURL(url);
            
            const successMsg = `✅ QR ID card generated successfully!\nFile: QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            alert(successMsg);
        }, 'image/png', 1.0);
    };
    
    qrImage.onerror = function() {
        alert('❌ Error loading QR code image. Please make sure the QR code is generated first.');
    };
    
    // Load the QR image
    qrImage.src = qrImg.src;
}
-->

<!-- g3 -->

function printQRCode() {
    const qrImg = document.querySelector('#qr-code img');
    if (!qrImg) return;
    
    // Get student data from the form
    const lrn = document.getElementById('student-id').value;
    const firstName = document.getElementById('first-name').value;
    const middleName = document.getElementById('middle-name').value;
    const lastName = document.getElementById('last-name').value;
    const fullName = `${lastName}, ${firstName} ${middleName}`;
    
    // Create a canvas to generate the ID card image
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    // ID card dimensions (portrait - 2.125" x 3.375" at 300 DPI)
    const cardWidth = 638;   // 2.125 * 300
    const cardHeight = 1012; // 3.375 * 300
    canvas.width = cardWidth;
    canvas.height = cardHeight;
    
    // Create image object for QR code
    const qrImage = new Image();
    qrImage.crossOrigin = 'anonymous';
    
    qrImage.onload = function() {
        // Set solid background color
        ctx.fillStyle = '#f5faff';
        ctx.fillRect(0, 0, cardWidth, cardHeight);
        
        // Add subtle pattern overlay
        ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
        for (let i = 0; i < cardHeight; i += 20) {
            ctx.fillRect(0, i, cardWidth, 10);
        }
        
        // Draw card background with rounded corners and shadow
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(15, 15, cardWidth - 30, cardHeight - 30, 25);
        ctx.shadowColor = 'rgba(0, 0, 0, 0.2)';
        ctx.shadowBlur = 20;
        ctx.shadowOffsetX = 5;
        ctx.shadowOffsetY = 5;
        ctx.fill();
        ctx.shadowColor = 'transparent';
        
        // Add elegant double border
        ctx.strokeStyle = '#1e40af';
        ctx.lineWidth = 3;
        ctx.beginPath();
        ctx.roundRect(15, 15, cardWidth - 30, cardHeight - 30, 25);
        ctx.stroke();
        ctx.strokeStyle = '#60a5fa';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.roundRect(18, 18, cardWidth - 36, cardHeight - 36, 22);
        ctx.stroke();
        
        // Add header section with solid color
        const headerHeight = 90;
        ctx.fillStyle = '#2563eb';
        ctx.beginPath();
        ctx.roundRect(25, 25, cardWidth - 50, headerHeight, 15);
        ctx.fill();
        
        // Add header text with elegant font, centered
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 30px "Roboto", Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('STUDENT ID', cardWidth / 2, 80); // Adjusted Y for vertical centering
        
        // Calculate QR code size with 10px padding
        const cardPadding = 25;
        const qrPadding = 10;
        const availableWidth = cardWidth - (cardPadding * 2) - (qrPadding * 2);
        const qrSize = availableWidth;
        const qrX = cardPadding + qrPadding;
        const qrY = headerHeight + 60;
        
        // Add QR code background with subtle shadow
        ctx.fillStyle = 'rgba(0, 0, 0, 0.08)';
        ctx.beginPath();
        ctx.roundRect(qrX - 5, qrY - 5, qrSize + 10, qrSize + 10, 10);
        ctx.fill();
        
        // Add white background for QR code
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        ctx.roundRect(qrX, qrY, qrSize, qrSize, 8);
        ctx.fill();
        
        // Draw QR code
        ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);
        
        // Add decorative line below QR code
        const lineY = qrY + qrSize + 40;
        ctx.strokeStyle = '#2563eb';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(50, lineY);
        ctx.lineTo(cardWidth - 50, lineY);
        ctx.stroke();
        
        // Set font for LRN with enhanced styling
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 32px "Roboto", Arial, sans-serif';
        ctx.textAlign = 'center';
        
        // Add LRN with subtle shadow
        const lrnText = `LRN: ${lrn}`;
        const lrnY = lineY + 60;
        ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
        ctx.fillText(lrnText, cardWidth / 2 + 2, lrnY + 2);
        ctx.fillStyle = '#1e293b';
        ctx.fillText(lrnText, cardWidth / 2, lrnY);
        
        // Set font for Full Name with dynamic sizing
        const nameLength = fullName.length;
        let fontSize = nameLength > 30 ? 22 : nameLength > 25 ? 26 : nameLength > 20 ? 28 : 30;
        ctx.font = `bold ${fontSize}px "Roboto", Arial, sans-serif`;
        ctx.fillStyle = '#1e293b';
        
        // Handle long names by wrapping text
        const maxWidth = cardWidth - 80;
        const words = fullName.split(' ');
        let line = '';
        let lines = [];
        
        for (let i = 0; i < words.length; i++) {
            const testLine = line + words[i] + ' ';
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;
            
            if (testWidth > maxWidth && i > 0) {
                lines.push(line.trim());
                line = words[i] + ' ';
            } else {
                line = testLine;
            }
        }
        lines.push(line.trim());
        
        // Draw full name with enhanced styling
        const nameStartY = lrnY + 70;
        const lineHeight = fontSize + 12;
        lines.forEach((line, index) => {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
            ctx.fillText(line, cardWidth / 2 + 1, nameStartY + (index * lineHeight) + 1);
            ctx.fillStyle = '#1e293b';
            ctx.fillText(line, cardWidth / 2, nameStartY + (index * lineHeight));
        });
        
        // Add footer decoration with school name
        const footerY = cardHeight - 60;
        ctx.fillStyle = '#2563eb';
        ctx.font = 'italic 22px "Roboto", Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Sample University', cardWidth / 2, footerY);
        
        // Add decorative elements (small circles)
        ctx.fillStyle = '#60a5fa';
        for (let i = 0; i < 5; i++) {
            const dotX = (cardWidth / 6) * (i + 1);
            ctx.beginPath();
            ctx.arc(dotX, footerY + 20, 4, 0, Math.PI * 2);
            ctx.fill();
        }
        
        // Convert canvas to blob and automatically download
        canvas.toBlob(function(blob) {
            const downloadLink = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            downloadLink.href = url;
            downloadLink.download = `QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            downloadLink.style.display = 'none';
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            URL.revokeObjectURL(url);
            
            const successMsg = `✅ QR ID card generated successfully!\nFile: QR_ID_${lrn}_${lastName}_${firstName}_${middleName}.png`;
            alert(successMsg);
        }, 'image/png', 1.0);
    };
    
    qrImage.onerror = function() {
        alert('❌ Error loading QR code image. Please make sure the QR code is generated first.');
    };
    
    // Load the QR image
    qrImage.src = qrImg.src;
}