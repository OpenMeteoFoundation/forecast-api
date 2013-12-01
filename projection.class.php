<?php

class Projection {

    private $rebydx;
    private $ctl1r;
    private $cone;
    private $hemi;
    private $rad_per_deg;
    private $standard_parallel_1;
    private $standard_parallel_2;
    private $longitude_of_central_meridian;
    private $polei;
    private $polej;
    private $sw_corner_lon;
    private $sw_corner_lat;
    private $earth_radius;
    private $grid_dx;
    private $grid_dy;
    private $grid_nx;
    private $grid_ny;

  function __construct(&$index, $domain) {
    
    // hardcoded values for EU12
    // TODO: fetch from run parameters
    
    $this->standard_parallel_1=47.5;
    $this->standard_parallel_2=47.5;
    $this->longitude_of_central_meridian=4.;
    $this->earth_radius=6.37e+06;
    $this->grid_dx=12000;
    $this->grid_dy=12000;
    $this->sw_corner_lon=-24.6064;
    $this->sw_corner_lat=26.3683;
    $this->grid_nx=495;
    $this->grid_ny=309;
    
    $this->_init_proj();
    
  }

  function latlon_to_xy ($lat, $lon) {
  
    // from http://www.mmm.ucar.edu/wrf/src/read_wrf_nc.f
    
    //! Compute deltalon between known longitude and standard lon and ensure
    //! it is not in the cut zone
    $deltalon = $lon - $this->longitude_of_central_meridian;
    if ($deltalon > 180.) {
      $deltalon = $deltalon - 360.;
    } else if ($deltalon < -180.) {
      $deltalon = $deltalon + 360.;
    }
    //! Radius to desired point
    $rm = $this->rebydx * $this->ctl1r/$this->cone * pow(
         (tan((90.*$this->hemi-$lat)*$this->rad_per_deg/2.) /
         tan((90.*$this->hemi-$this->standard_parallel_1)*$this->rad_per_deg/2.)), $this->cone);

    $arg = $this->cone*($deltalon*$this->rad_per_deg);
    $i = $this->polei + $this->hemi * $rm * sin($arg);
    $j = $this->polej - $rm * cos($arg);
    
    //! Finally, if we are in the southern hemisphere, flip the i/j
    //! values to a coordinate system where (1,1) is the SW corner
    //! (what we assume) which is different than the original NCEP
    //! algorithms which used the NE corner as the origin in the
    //! southern hemisphere (left-hand vs. right-hand coordinate?)
    $i = $this->hemi * $i;
    $j = $this->hemi * $j;
    
    //! check if we are on the grid
    $xerr=0;
    $yerr=0;
    
    if ($i<0) {
      $xerr=$i*$this->grid_dx;
      $i=0;
    } else if ($i >= $this->grid_nx) {
      $xerr=($i-$this->grid_nx)*$this->grid_dx;
      $i=$this->grid_nx-1;
    } else {
      $iround=round($i);
      $xerr=($i-$iround)*$this->grid_dx;
      $i=$iround;
    }
    
    if ($j<0) {
      $yerr=$j*$this->grid_dy;
      $j=0;
    } else if ($j >= $this->grid_ny) {
      $yerr=($j-$this->grid_ny)*$this->grid_dy;
      $j=$this->grid_ny-1;
    } else {
      $jround=round($j);
      $yerr=($j-$jround)*$this->grid_dy;
      $j=$jround;
    }
    
    $xerr=round($xerr);
    $yerr=round($yerr);
    
    return array('x'=>$i, 'y'=>$j, 'x_error'=>$xerr, 'y_error'=>$yerr);
  }
  
  function _init_proj () {
    
    if (abs($this->standard_parallel_2) > 90) {
      $this->standard_parallel_2=$this->standard_parallel_1;
    }
    
    $this->rad_per_deg = M_PI/180.;
    
    //! Earth radius divided by dx
    $this->rebydx = $this->earth_radius / $this->grid_dx;
    
    //! 1 for NH, -1 for SH
    $this->hemi=1.0;
    if ( $this->standard_parallel_1 < 0.0 ) $this->hemi = -1.0;
    
    //! Cone factor for LC projections
    if (abs($this->standard_parallel_1-$this->standard_parallel_2) > 0.1) {
      $this->cone=(log(cos($this->standard_parallel_1*$this->rad_per_deg))-
      log(cos($this->standard_parallel_2*$this->rad_per_deg))) /
      (log(tan((90.-abs($this->standard_parallel_1))*$this->rad_per_deg*0.5 ))-
      log(tan((90.-abs($this->standard_parallel_2))*$this->rad_per_deg*0.5 )) );
    } else {
      $this->cone = sin(abs($this->standard_parallel_1)*$this->rad_per_deg );
    }
    
    //! X/Y location of known lon/lat
    $knowni = 0;
    $knownj = 0;
    
    //! Compute longitude differences and ensure we stay out of the forbidden "cut zone"
    
    $deltalon1 = $this->sw_corner_lon - $this->longitude_of_central_meridian;
    if ($deltalon1 > 180.) {
      $deltalon1 = $deltalon1 - 360.;
    } else if ($deltalon1 < -180.) {
      $deltalon1 = $deltalon1 + 360.;
    }
    
    //! Convert truelat1 to radian and compute COS for later use
    $tl1r = $this->standard_parallel_1 * $this->rad_per_deg;
    $this->ctl1r = cos($tl1r);
    
    //! Compute the radius to our known lower-left (SW) corner
    $rsw = $this->rebydx * $this->ctl1r / $this->cone * pow(
         (tan((90.*$this->hemi-$this->sw_corner_lat)*$this->rad_per_deg/2.) /
                tan((90.*$this->hemi-$this->standard_parallel_1)*$this->rad_per_deg/2.)), $this->cone);
    
    //! Find pole point
    $arg = $this->cone*($deltalon1*$this->rad_per_deg);
    $this->polei = $this->hemi*$knowni - $this->hemi * $rsw * sin($arg);
    $this->polej = $this->hemi*$knownj + $rsw * cos($arg);
    
  }

}