#include <iostream>
#include <string>
#include <map>
#include <netcdfcpp.h>

#define NX 495
#define NY 309

class Data {

public:
  Data();
  ~Data();
  void add(const std::string &id,
		const std::string &filename,
		const std::string &varname,
		const int &zlevel=0
	  );
  void remove(const std::string &id);
  float get(const std::string &id, const int &x, const int &y);

private:
  std::map<std::string,float*> map_;
  std::map<std::string,float*>::iterator it_;

  
};