#include "projection.h"

// from http://www.mmm.ucar.edu/wrf/src/read_wrf_nc.f

Projection::Projection() {

  standard_parallel_1=47.5;
  standard_parallel_2=47.5;
  earth_radius=6370000;
  grid_dx=12000;
  grid_dy=12000;
  grid_nx=495;
  grid_ny=309;
  sw_corner_lon=-24.6064;
  sw_corner_lat=26.3683;
  longitude_of_central_meridian=4.;

  init_();

}

void Projection::init_() {

  rad_per_deg=M_PI/180.;

  //! Earth radius divided by dx
  rebydx = earth_radius / grid_dx;

  //! 1 for NH, -1 for SH
  hemi=1.0;
  if ( standard_parallel_1 < 0.0 ) hemi = -1.0;

  //! Cone factor for LC projections
  if (fabs(standard_parallel_1-standard_parallel_2) > 0.1) {
    cone=(log(cos(standard_parallel_1*rad_per_deg))-            
    log(cos(standard_parallel_2*rad_per_deg))) /          
    (log(tan((90.-fabs(standard_parallel_1))*rad_per_deg*0.5 ))- 
    log(tan((90.-fabs(standard_parallel_2))*rad_per_deg*0.5 )) );
  } else {
    cone = sin(fabs(standard_parallel_1)*rad_per_deg);
  }

  //! X/Y location of known lon/lat
  knowni   =   0; 
  knownj   =   0;

  //! Compute longitude differences and ensure we stay out of the forbidden "cut zone"
  deltalon1 = sw_corner_lon - longitude_of_central_meridian;
  if (deltalon1 > 180.) {
    deltalon1 = deltalon1 - 360.;
  } else if (deltalon1 < -180.) {
    deltalon1 = deltalon1 + 360.;
  }

  //! Convert truelat1 to radian and compute COS for later use
  tl1r = standard_parallel_1 * rad_per_deg;
  ctl1r = cos(tl1r);

  //! Compute the radius to our known lower-left (SW) corner
  rsw = rebydx * ctl1r / cone * pow(
    (tan((90.*hemi-sw_corner_lat)*rad_per_deg/2.) / 
    tan((90.*hemi-standard_parallel_1)*rad_per_deg/2.)), cone);
  
  //! Find pole point
  arg = cone*(deltalon1*rad_per_deg);
  polei = hemi*knowni - hemi * rsw * sin(arg);
  polej = hemi*knownj + rsw * cos(arg);

}

Projection::GridPoint Projection::latLonToGridXY(Latitude const &lat, Longitude const &lon) {
  
  GridPoint gridPoint;
  double i, j;
  
  double deltalon, rm;
  
  //! Compute deltalon between known longitude and standard lon and ensure
  //! it is not in the cut zone
  deltalon = lon - longitude_of_central_meridian;
  if (deltalon > 180.) {
    deltalon = deltalon - 360.;
  } else if (deltalon < -180.) {
    deltalon = deltalon + 360.;
  }
  //! Radius to desired point
  rm = rebydx * ctl1r/cone * pow(
	(tan((90.*hemi-lat)*rad_per_deg/2.) /
	tan((90.*hemi-standard_parallel_1)*rad_per_deg/2.)), cone);

  arg = cone*(deltalon*rad_per_deg);
  i = polei + hemi * rm * sin(arg);
  j = polej - rm * cos(arg);
  
  //! Finally, if we are in the southern hemisphere, flip the i/j
  //! values to a coordinate system where (1,1) is the SW corner
  //! (what we assume) which is different than the original NCEP
  //! algorithms which used the NE corner as the origin in the 
  //! southern hemisphere (left-hand vs. right-hand coordinate?)
  i *= hemi;
  j *= hemi;
  

  if (i<0.) {
    gridPoint.xError=i*grid_dx;
    gridPoint.x=0;
  } else if (i >= grid_nx) {
    gridPoint.xError=(i-grid_nx)*grid_dx;
    gridPoint.x=grid_nx-1;
  } else {
    gridPoint.x=(int)i;
    gridPoint.xError=(i-gridPoint.x)*grid_dx;
  }
  
  if (j<0.) {
    gridPoint.yError=j*grid_dy;
    gridPoint.y=0;
  } else if (j >= grid_ny) {
    gridPoint.yError=(j-grid_ny)*grid_dy;
    gridPoint.y=grid_ny-1;
  } else {
    gridPoint.y=(int)j;
    gridPoint.yError=(j-gridPoint.y)*grid_dy;
  }
  
  return gridPoint;
}

