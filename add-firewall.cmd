@echo off
netsh advfirewall firewall add rule name="Laravel8080" dir=in action=allow protocol=TCP localport=8080
netsh advfirewall firewall add rule name="Laravel8080Out" dir=out action=allow protocol=TCP localport=8080
echo DONE
pause
