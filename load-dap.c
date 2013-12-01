#include <stdio.h>
#include <stdlib.h>
#include <netcdf.h>
#include <sys/shm.h>

#define SHM_KEY  1
#define NHOURS   2
#define NVARS    3
#define VAR_ID   4
#define FRAME    5
#define URL      6
#define VAR_NAME 7
#define Z_LEVEL  8

int ncid;

void ne (int status) {
  if (status != NC_NOERR) {
    fprintf(stderr, "%s\n", nc_strerror(status));
    nc_close(ncid);
    exit(EXIT_FAILURE);
  }
}

int main(int argc, char *argv[]) {
  
  int varid;
  int ndims;
  int dimids[NC_MAX_VAR_DIMS];
  
  size_t nx, ny, size, var_size, var_offset, i;
  
  int shm_id, shm_key;
  int nhours, nvars, var_i, frame;
  
  float* shm_p;
  float* data_p;
  
  if (argc != 9) {
    fprintf(stdout, "usage: ./load-dap {SHM_KEY} {NHOURS} {NVARS} {VAR_ID} {FRAME} {URL} {VAR_NAME} {Z_LEVEL}\n");
    exit(EXIT_FAILURE);
  }
  
  ne(nc_open(argv[URL], 0, &ncid));
  ne(nc_inq_varid (ncid, argv[VAR_NAME], &varid));
  ne(nc_inq_varndims(ncid, varid, &ndims));
  ne(nc_inq_vardimid(ncid, varid, dimids));
  
  if (ndims==2) {
    ne(nc_inq_dimlen(ncid, dimids[0], &ny));
    ne(nc_inq_dimlen(ncid, dimids[1], &nx));
  } else if (ndims==3) {
    ne(nc_inq_dimlen(ncid, dimids[1], &ny));
    ne(nc_inq_dimlen(ncid, dimids[2], &nx));
  }
  
  shm_key=atoi(argv[SHM_KEY]);
  shm_id=shmget(shm_key, 0, 0644);
  if (shm_id == -1) {
    fprintf(stderr, "Shared memory shmget() failed\n");
    exit(EXIT_FAILURE);
  }

  shm_p = shmat(shm_id, NULL, 0);
  if (shm_p == (void *)-1) {
    fprintf(stderr, "Shared memory shmat() failed\n");
    exit(EXIT_FAILURE);
  }
  
  size=nx*ny;
  data_p=malloc(size*sizeof(float));
  if (data_p == NULL) {
    fprintf(stderr, "malloc() failed\n");
    exit(EXIT_FAILURE);
  }
  
  if (ndims==2) {
    ne(nc_get_var_float(ncid, varid, data_p));
  } else {
    size_t start[3]={0,0,0};
    size_t count[3]={1,ny,nx};
    start[0]=atoi(argv[Z_LEVEL]);
    ne(nc_get_vara_float(ncid, varid, start, count, data_p));
  }
  
  // run[point[var[frames[]]]]
  nhours=atoi(argv[NHOURS]);
  nvars=atoi(argv[NVARS]);
  frame=atoi(argv[FRAME]);
  var_i=atoi(argv[VAR_ID]);
  size_t index;
  var_size=nhours*nvars;
  var_offset=var_i*nhours+frame;
  for (i=0; i<size; i++) {
    index=i*var_size+var_offset;
    shm_p[index]=data_p[i];
  }  
  
  free(data_p);
  
  if(shmdt(shm_p)==-1){
    fprintf(stderr, "Shared memory shmdt() failed\n");
    exit(EXIT_FAILURE);
  }
  
  nc_close(ncid);
  
  return 0;
}