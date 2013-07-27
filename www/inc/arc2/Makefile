all: parsers/ARC2_SPARQLPlusParser.php

parsers/ARC2_SPARQLPlusParser.php: parsers/ARC2_SPARQLParser.php
	wget -O $@ https://raw.github.com/semsol/arc2/master/parsers/ARC2_SPARQLPlusParser.php

parsers/ARC2_SPARQLParser.php: parsers/ARC2_TurtleParser.php
	wget -O $@ https://raw.github.com/semsol/arc2/master/parsers/ARC2_SPARQLParser.php

parsers/ARC2_TurtleParser.php: parsers/ARC2_RDFParser.php
	wget -O $@ https://raw.github.com/semsol/arc2/master/parsers/ARC2_TurtleParser.php

parsers/ARC2_RDFParser.php: ARC2_Class.php parsers
	wget -O $@ https://raw.github.com/semsol/arc2/master/parsers/ARC2_RDFParser.php

parsers:
	mkdir -p parsers

ARC2_Class.php: ARC2.php
	wget -O $@ https://raw.github.com/semsol/arc2/master/ARC2_Class.php

ARC2.php:
	wget -O $@ https://raw.github.com/semsol/arc2/master/ARC2.php
