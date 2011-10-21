XX		= g++
INCLUDES	= -I./
LIB		= `mysql_config --libs` 
FLAGS		= -Wall -ggdb -fPIC -shared
SOURCE		= $(wildcard *.cpp)
OBJS		= $(patsubst %.cpp, %.o, $(SOURCE))
TARGET		= ./liboc.so

all: $(TARGET)

$(TARGET): $(OBJS)
	$(XX) -o $(TARGET) $(OBJS) $(FLAGS) $(LIB)
	rm *.o

%.o: %.cpp
	$(XX) $(FLAGS) $(INCLUDES) -c $< -o $@

clean:
	rm -rf *.o $(TARGET)
	@make_clean_sub_dir()\
        {\
                for dir in `ls`; do \
                        if [ -d $$dir ]; then \
                                cd $$dir;\
                                make_clean_sub_dir;\
                                if [ -f makefile -o -f Makefile ]; then\
                                        make clean;\
                                fi;\
                                cd ..;\
                        fi;\
                done;\
        };\
        make_clean_sub_dir

rebuild: clean all

c: clean

r: rebuild
