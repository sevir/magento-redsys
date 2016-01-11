<?php
/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
* 
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
* 
* Redsys es titular de todos los derechos de propiedad intelectual e industrial
* del software.
* 
* Quedan expresamente prohibidas la reproducción, la distribución y la
* comunicación pública, incluida su modalidad de puesta a disposición con fines
* distintos a los descritos en las Condiciones de uso.
* 
* Redsys se reserva la posibilidad de ejercer las acciones legales que le
* correspondan para hacer valer sus derechos frente a cualquier infracción de
* los derechos de propiedad intelectual y/o industrial.
* 
* Redsys Servicios de Procesamiento, S.L., CIF B85955367
*/
class Mage_Adminhtml_Model_System_Config_Source_Entornoptions
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('Real')),
            array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Sis-d')),
            array('value' => 2, 'label'=>Mage::helper('adminhtml')->__('Sis-i')),
			array('value' => 3, 'label'=>Mage::helper('adminhtml')->__('Sis-t')),
        );
    }

	public function toArray()
	{
		return array(
			0 => Mage::helper('adminhtml')->__('Real'),
			1 => Mage::helper('adminhtml')->__('Sis-d'),
			2 => Mage::helper('adminhtml')->__('Sis-i'),
			3 => Mage::helper('adminhtml')->__('Sis-t'),
		);
	}
}
?>