<?php

class RDFIOTestData {

    function getTestImportData() {
        $testImportData = '<rdf:RDF
				xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
				xmlns:cd="http://www.recshop.fake.org/cd#"
				xmlns:countries="http://www.countries.fake.org/onto/"
				xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
				>
    
				<rdf:Description
				rdf:about="http://www.recshop.fake.org/cd/Empire Burlesque">
				<cd:artist>Bob Dylan</cd:artist>
				<cd:country rdf:resource="http://www.countries.fake.org/onto/USA"/>
				<cd:company>Columbia</cd:company>
				<cd:price>10.90</cd:price>
				<cd:year>1985</cd:year>
				</rdf:Description>
    
				<rdf:Description
				rdf:about="http://www.recshop.fake.org/cd/Hide your heart">
				<cd:artist>Bonnie Tyler</cd:artist>
				<cd:country>UK</cd:country>
				<cd:company>CBS Records</cd:company>
				<cd:price>9.90</cd:price>
				<cd:year>1988</cd:year>
				</rdf:Description>
    
				<!-- rdf:Description
				rdf:about="http://www.countries.fake.org/onto/USA">
				<rdfs:label>USA</rdfs:label>
				</rdf:Description -->
    
				<rdf:Description
				rdf:about="http://something.totally.unrelated.to/its/label">
				<rdfs:label>SomeTotallyUnrelatedLabel</rdfs:label>
				</rdf:Description>                
                
                <rdf:Description rdf:about="http://www.countries.fake.org/onto/Albums">
				<rdfs:subClassOf rdf:resource="http://www.countries.fake.org/onto/MediaCollections"/>
				</rdf:Description>
				</rdf:RDF>';
        return $testImportData;
    }    

    function getInvalidTestImportData() {
        $testImportData = '< rdf:RDF
				xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
				xmlns:cd="http://www.recshop.fake.org/cd#"
				xmlns:countries="http://www.countries.fake.org/onto/"
				xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
				>
    
				<rdf:Description
				rdf:about="http://www.recshop.fake.org/cd/Empire Burlesque">
				<cd:artist>Bob Dylan</cd:artist>
				<cd:country rdf:resource="http://www.countries.fake.org/onto/USA"/>
				<cd:company>Columbia</cd:company>
				<cd:price>10.90</cd:price>
				<cd:year>1985</cd:year>
				</rdf:Description>
    
				<rdf:Description
				rdf:about="http://www.recshop.fake.org/cd/Hide your heart">
				<cd:artist>Bonnie Tyler</cd:artist>
				<cd:country>UK</cd:country>
				<cd:company>CBS Records</cd:company>
				<cd:price>9.90</cd:price>
				<cd:year>1988</cd:year>
				</rdf:Description>
    
				<rdf:Description
				rdf:about="http://www.countries.fake.org/onto/USA">
				<rdfs:label>USA</rdfs:label>
				</rdf:Description>
    
				<rdf:Description rdf:about="http://www.countries.fake.org/onto/Albums">
				<rdfs:subClassOf rdf:resource="http://www.countries.fake.org/onto/MediaCollections"/>
				</rdf:Description>
				</rdf:RDF>';
        return $testImportData;
    }

	function getTemplateData() {
		$testImportData = "<noinclude>This template is for albums of recorded music
				</noinclude>
				<includeonly>
				{| class=\"wikitable\"
				|-
				|'''Equivalent URI:''' 
				|[[Equivalent URI::{{{Equivalent URI}}}]]
				|-
				|'''Artist:''' 
				|[[Cd:artist::{{{Artist}}}]]
				|-
				| '''Country:''' 
				|[[Cd:country::{{{Country}}}]]
				|-
				| '''Company:''' 
				|[[Cd:company::{{{Company}}}]]
				|-
				| '''Price:''' 
				|[[Cd:price::{{{Price}}}]]
				|-
				| '''Year:''' 
				|[[Cd:year::{{{Year}}}]]
				|}
				</includeonly>";
		return $testImportData;
	}

	function getPageDataWithoutTemplate() {
		$testImportData = '';
		return $testImportData;
	}

	function getPageDataWithTemplate() {
		$testImportData = '{{Album|Equivalent URI=http://www.recshop.fake.org/cd/Empire Burlesque|Company=Colombia|Price=10.90|Year=1985|Artist=Bob Dylan|Country=Countries:USA}}

[[Category:Album]]';
		return $testImportData;
	}
}
