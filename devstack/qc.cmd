@echo off
rem QuickCart developer CLI - see devstack\qc.ps1 (run "devstack\qc help")
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0qc.ps1" %*
exit /b %ERRORLEVEL%
