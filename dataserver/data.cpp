#include "data.h"

Data::Data() {
  
}


void Data::add (const std::string &id,
		const std::string &filename,
		const std::string &varname,
		const int &zlevel) {
  
  std::cout << "Add " << id << '\n';
  
  if (map_.find(id) != map_.end()) {
    throw std::string ("la clé existe déjà");
  }
  
  map_[id] = new float [NX*NY];
  
  std::cout << "- open " << filename << '\n';
  NcFile ncFile (filename.c_str());
  
  std::cout << "- get " << varname << '\n';
  NcVar* ncVar=ncFile.get_var(varname.c_str());
  
  int success;
  switch (ncVar->num_dims()) {
    case 2:
      std::cout << "* 2D\n";
      success=ncVar->get((float*)map_[id], NY, NX);
      break;
    case 3:
      std::cout << "* 3D : z=" << zlevel <<  "\n";
      if(!ncVar->set_cur(zlevel, 0, 0)) {
	throw std::string ("erreur set cur");
      }
      success=ncVar->get((float*)map_[id], 1, NY, NX);
      break;
    default:
      throw std::string ("erreur num dims");
  }
    
  if (!success) {
    throw std::string ("erreur var get");
  }
  
  ncFile.close();
  
}

float Data::get(const std::string &id, const int &x, const int &y) {
  if (map_.find(id) == map_.end()) {
    throw std::string ("la clé n'existe pas");
  }
  return map_[id][x+NX*y];
}

void Data::remove (const std::string &id) {
  
  it_ = map_.find(id);
  if (it_ == map_.end()) {
    throw std::string ("la clé n'existe pas");
  }
  
  std::cout << "Free " << it_->first << '\n';
  delete(it_->second);
  
  map_.erase (it_);
  
}

Data::~Data() {
  std::cout << "Freeing at close " << '\n';
  for (it_=map_.begin(); it_!=map_.end(); ++it_) {
    std::cout << "- free " << it_->first << '\n';
    delete(it_->second);
  }
}