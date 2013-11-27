#include <cmath>

class Projection {

public:
    
    typedef int GridX;
    typedef int GridY;
    typedef double Latitude;
    typedef double Longitude;
    typedef double Meters;
    
    struct GridPoint {
      GridX x;
      GridY y;
      Meters xError;
      Meters yError;
    };
    
    Projection();
    
    GridPoint latLonToGridXY(Latitude const &, Longitude const &); 
    
  
private:
    void init_();

    double standard_parallel_1;
    double standard_parallel_2;
    double earth_radius;
    int grid_dx;
    int grid_dy;
    int grid_nx;
    int grid_ny;
    double sw_corner_lon;
    double sw_corner_lat;
    double longitude_of_central_meridian;
    double rad_per_deg;
    double rebydx;
    double hemi;
    double cone;
    int knowni;
    int knownj;
    double deltalon1;
    double tl1r;
    double ctl1r;
    double rsw;
    double arg;
    int polei;
    int polej;
    
};