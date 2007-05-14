// This script counts the number of available articles in the history file of INN.
// The first argument passed is the file to open
//
// Copyright Antoine Delvaux 2003-2007, 2001 and before Marc Roger
//
//  * This program is free software; you can redistribute it and/or
//  * modify it under the terms of the GNU General Public License
//  * as published by the Free Software Foundation; either version 2
//  * of the License, or (at your option) any later version.
//  *
//  * This program is distributed in the hope that it will be useful,
//  * but WITHOUT ANY WARRANTY; without even the implied warranty of
//  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  * GNU General Public License for more details.
//  *
//  * You should have received a copy of the GNU General Public License
//  * along with this program; if not, write to the Free Software
//  * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA,
//  * or go to http://www.gnu.org/copyleft/gpl.html
//  

#include <stdio.h>

main(int argc, char **argv)
{
int c,articles=0,lines=0;
char s[1024],*t;
FILE *F;

F=fopen(argv[1], "r");

while (fgets(s,1023,F)) {
	lines++;
	t=s;
	c=0;
	while(*++t) {
		// looks for tabs
		if (*t == '	') 
			c++;
	}	
	if (c>1) {
		// if wa have found more than one tabs, it means the article
		// has a begin and an end, so it is really present
		articles++;
	}
}
fclose(F);
printf("%d\n", articles);
}
		
		

