# Script PowerShell để convert Excel sang CSV
# Yêu cầu: Có Microsoft Excel cài đặt

$excelFile = "Phân tích dự án tốt nghiệp.xlsx"
$csvFile = "phan_tich_du_an.csv"

try {
    # Tạo object Excel
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    
    # Mở file Excel
    $workbook = $excel.Workbooks.Open((Get-Item $excelFile).FullName)
    
    # Lấy sheet đầu tiên
    $worksheet = $workbook.Worksheets.Item(1)
    
    # Export sang CSV
    $worksheet.SaveAs((Get-Item $csvFile).FullName, 6) # 6 = CSV format
    
    # Đóng và cleanup
    $workbook.Close()
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
    
    Write-Host "Đã convert thành công: $csvFile" -ForegroundColor Green
}
catch {
    Write-Host "Lỗi: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Hãy thử cách khác (Google Sheets hoặc LibreOffice)" -ForegroundColor Yellow
}

