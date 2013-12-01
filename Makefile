CC		= gcc
CFLAGS		= -c -Wall
LDFLAGS		= -lnetcdf
SOURCES		= load-dap.c
INCLUDES	= 
OBJECTS		= $(SOURCES:.c=.o)
TARGET		= load-dap

all: $(SOURCES) $(TARGET)

$(TARGET): $(OBJECTS) 
	$(CC) $(OBJECTS) $(LDFLAGS) -o $@

.cpp.o:
	$(CC) $(CFLAGS) $(INCLUDES) $< -o $@

clean:
	rm -rf $(OBJECTS) $(TARGET)
