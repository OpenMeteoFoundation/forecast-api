CC		= g++
CFLAGS		= -c -Wall
LDFLAGS		= -lpthread  -lnetcdf_c++
SOURCES		= main.cpp projection.cpp data.cpp ./threads/thread.cpp ./tcpsockets/tcpacceptor.cpp ./tcpsockets/tcpstream.cpp
INCLUDES	= -I./threads -I./wqueue -I./tcpsockets
OBJECTS		= $(SOURCES:.cpp=.o)
TARGET		= dataserver

all: $(SOURCES) $(TARGET)

$(TARGET): $(OBJECTS) 
	$(CC) $(OBJECTS) $(LDFLAGS) -o $@

.cpp.o:
	$(CC) $(CFLAGS) $(INCLUDES) $< -o $@

clean:
	rm -rf $(OBJECTS) $(TARGET)
