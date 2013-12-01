api for Open Meteo Forecast
===========================

http://api.ometfn.net/0.1/forecast

dependencies
------------
script is provided for ubuntu
run ./install_dependencies.sh


this software make heavy use of shared memory
you need change maximum allowed shm size
see ./install_sysctl.sh

build
-----

cd dataserver
make
