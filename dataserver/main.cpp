/*
   main.cpp

   Multithreaded work queue based example server in C++.
  
   ------------------------------------------

   Copyright © 2013 [Vic Hargrave - http://vichargrave.com]

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

#include <stdio.h>
#include <stdlib.h>
#include <string>
#include "thread.h"
#include "wqueue.h"
#include "tcpacceptor.h"

#include <sstream>
#include <vector>

#include "projection.h"
#include "data.h"

Projection projection;
Data data;


class WorkItem
{
    TCPStream* m_stream;
 
  public:
    WorkItem(TCPStream* stream) : m_stream(stream) {}
    ~WorkItem() { delete m_stream; }
 
    TCPStream* getStream() { return m_stream; }
};

class ConnectionHandler : public Thread
{
    wqueue<WorkItem*>& m_queue;
 
  public:
    ConnectionHandler(wqueue<WorkItem*>& queue) : m_queue(queue) {}
 
    void* run() {
        // Remove 1 item at a time and process it. Blocks if no items are 
        // available to process.
        for (int i = 0;; i++) {
            printf("thread %lu, loop %d - waiting for item...\n", 
                   (long unsigned int)self(), i);
            WorkItem* item = m_queue.remove();
	    
            TCPStream* stream = item->getStream();

            char input[256];
            int len;
            while ((len = stream->receive(input, sizeof(input)-1)) != 0 ){
                input[len] = NULL;
		
		std::string istr (input, len);
		istr.erase(istr.find_last_not_of(" \n\r\t")+1);
		
		std::cout << '>' << istr << '\n';
		
		std::istringstream split(istr);
		std::vector<std::string> args;
		for (std::string each; std::getline(split, each, ' '); args.push_back(each));

		std::ostringstream out;
		
		try {
		  
		  int nargs=args.size();
		  if (nargs==1 && args[0].compare("list") == 0) {
		    
		    out << "todo\n";
		    
		  } else if (nargs==3 && args[0].compare("ll2xy") == 0) {
		    
		    float lat=atof(args[1].c_str());
		    float lon=atof(args[2].c_str());
		    
		    Projection::GridPoint gridPoint;
		    gridPoint=projection.latLonToGridXY(lat, lon);
		    
		    out << "ok " 
		    << gridPoint.x << ' '
		    << gridPoint.y << ' '
		    << gridPoint.xError << ' '
		    << gridPoint.yError << ' '
		    << '\n';
		    
		    
		  } else if (nargs==4 && args[0].compare("getxy") == 0) {
		    
		    int x=atof(args[2].c_str());
		    int y=atof(args[3].c_str());
		    
		    float val=data.get(args[1], x, y);
		    
		    out << "ok " << val << '\n';
		    
		  } else if (nargs==4 && args[0].compare("getll") == 0) {
		    
		    float lat=atof(args[2].c_str());
		    float lon=atof(args[3].c_str());
		    
		    Projection::GridPoint gridPoint;
		    gridPoint=projection.latLonToGridXY(lat, lon);
		    
		    float val=data.get(args[1], gridPoint.x, gridPoint.y);
		    
		    out << "ok " 
		    << val << ' '
		    << gridPoint.x << ' '
		    << gridPoint.y << ' '
		    << gridPoint.xError << ' '
		    << gridPoint.yError << ' '
		    << '\n';
		    
		  } else if ((nargs==4 || nargs==5) && args[0].compare("add") == 0) {
		    
		    if (nargs==4) {
		      data.add(args[1], args[2], args[3]);
		    } else {
		      int zlevel=atoi(args[4].c_str());
		      data.add(args[1], args[2], args[3], zlevel);
		    }
		    out << "ok\n";
		    
		  } else if (nargs==2 && args[0].compare("rm") == 0) {
		    
		    data.remove(args[1]);
		    
		    out << "ok\n";
		    
		  } else {
		    throw std::string ("bad request");
		  }
		  
		} catch (std::string err) {
		  out << err << '\n';
		  std::cerr << "ERROR: " << err << '\n';
		}
		
		std::string outstr = out.str();
		stream->send(outstr.c_str(), outstr.size());
		
            }
            delete item; 
        }

        // Should never get here
        return NULL;
    }
};


int main(int argc, char** argv)
{
    // Process command line arguments
    if ( argc < 3 || argc > 4 ) {
        printf("usage: %s <workers> <port> <ip>\n", argv[0]);
        exit(-1);
    }
    int workers = atoi(argv[1]);
    int port = atoi(argv[2]);
    string ip;
    if (argc == 4) { 
        ip = argv[3];
    }
 
    // Create the queue and consumer (worker) threads
    wqueue<WorkItem*>  queue;
    for (int i = 0; i < workers; i++) {
        ConnectionHandler* handler = new ConnectionHandler(queue);
        if (!handler) {
            printf("Could not create ConnectionHandler %d\n", i);
            exit(1);
        } 
        handler->start();
    }
 
    // Create an acceptor then start listening for connections
    WorkItem* item;
    TCPAcceptor* connectionAcceptor;
    if (ip.length() > 0) {
        connectionAcceptor = new TCPAcceptor(port, (char*)ip.c_str());
    }
    else {
        connectionAcceptor = new TCPAcceptor(port);        
    }                                        
    if (!connectionAcceptor || connectionAcceptor->start() != 0) {
        printf("Could not create an connection acceptor\n");
        exit(1);
    }

    // Add a work item to the queue for each connection
    while (1) {
        TCPStream* connection = connectionAcceptor->accept(); 
        if (!connection) {
            printf("Could not accept a connection\n");
            continue;
        }
        item = new WorkItem(connection);
        if (!item) {
            printf("Could not create work item a connection\n");
            continue;
        }
        queue.add(item);
    }
 
    // Should never get here
    exit(0);
}
