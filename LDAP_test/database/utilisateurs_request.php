<?php

//Gestion des clients
class ShowUtilisateursForm {

    private $pdo; 
    private $UtilisateursRecoveryByClientSQLRequest = "SELECT * FROM Utilisateurs WHERE clients_idclients = [0] ";
    private $UtilisateursRecoveryByIdSQLRequest = "SELECT * FROM Utilisateurs WHERE idutilisateurs = [0] ";
    private $UtilisateursUpdateSQLRequest = "UPDATE Utilisateurs SET Nom = \"[1]\", Extension = \"[2]\", TypePoste = \"[3]\",
    	AdresseMAC = \"[4]\",	SIPLogin = \"[5]\", SIPPassword = \"[6]\", SIPServeur = \"[7]\" WHERE idutilisateurs = [0]";
    private $UtilisateursBLFRecoveryByUtilisateurSQLRequest = "SELECT * FROM UtilisateursBLF WHERE utilisateurs_idutilisateurs = [0] ORDER BY Position";
    private $UtilisateursBLFRecoveryByIdSQLRequest = "SELECT * FROM UtilisateursBLF WHERE idblf = [0] ";
    private $UtilisateursBLFRecoveryByPositionSQLRequest = "SELECT * FROM UtilisateursBLF WHERE utilisateurs_idutilisateurs = [0] AND Position = [1] ";
    private $UtilisateursBLFUpdatePositionSQLRequest = "UPDATE UtilisateursBLF SET Position = [1] WHERE idblf = [0] ";
    private $UtilisateursBLFUpdateSQLRequest = "UPDATE UtilisateursBLF SET TypeBLF = \"[1]\", Etiquette = \"[2]\", Valeur = \"[3]\" WHERE idblf = [0]";
	private $UtilisateursBLFInsertSQLRequest = "INSERT INTO UtilisateursBLF (TypeBLF, Etiquette, Valeur, Position, utilisateurs_idutilisateurs)
                        VALUES (\"[2]\", \"[3]\", \"[4]\", [1], [0])";
		
    //Constructeur pour initialiser la connexion PDO
    function __construct($pdo) {
        $this->pdo = $pdo; 
    }
    
    // Récupération des utilisateurs d'un client
    function UtilisateursRecoveryByClient($idclient) {
		$sqlrequest = str_replace("[0]", $idclient,$this->UtilisateursRecoveryByClientSQLRequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupération d'un utilisateur par son id
    function UtilisateursRecoveryById($idutilisateur) {
		$sqlrequest = str_replace("[0]", $idutilisateur,$this->UtilisateursRecoveryByIdSQLRequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Enregistrement des données d'un utilisateur
    function UtilisateursUpdate($idutilisateur,$nom,$extension,$typeposte,$mac,$sipsrv,$siplog,$sippass) {
				$sqlrequest = str_replace("[0]", $idutilisateur,$this->UtilisateursUpdateSQLRequest);
				$sqlrequest = str_replace("[1]", $nom,$sqlrequest);
				$sqlrequest = str_replace("[2]", $extension,$sqlrequest);
				$sqlrequest = str_replace("[3]", $typeposte,$sqlrequest);
				$sqlrequest = str_replace("[4]", $mac,$sqlrequest);
				$sqlrequest = str_replace("[5]", $sipsrv,$sqlrequest);
				$sqlrequest = str_replace("[6]", $siplog,$sqlrequest);
				$sqlrequest = str_replace("[7]", $sippass,$sqlrequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /*-------------------------------- PARTIE BLF -----------------------------------*/
    
    // Récupération des BLF d'un utilisateur par son id
    function UtilisateursBLFRecoveryByUtilisateur($idutilisateur) {
				$sqlrequest = str_replace("[0]", $idutilisateur,$this->UtilisateursBLFRecoveryByUtilisateurSQLRequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupération d'une BLF par son id
    function UtilisateursBLFRecoveryById($idblf) {
				$sqlrequest = str_replace("[0]", $idblf,$this->UtilisateursBLFRecoveryByIdSQLRequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Récupération d'une BLF par sa position
    function UtilisateursBLFRecoveryByPosition($idutilisateur, $position) {
				$sqlrequest = str_replace("[0]", $idutilisateur,$this->UtilisateursBLFRecoveryByPositionSQLRequest);
				$sqlrequest = str_replace("[1]", $position,$sqlrequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Modification de la position d'une  BLF
    function UtilisateursBLFUpdatePosition($idblf, $newposition) {
				$sqlrequest = str_replace("[0]", $idblf,$this->UtilisateursBLFUpdatePositionSQLRequest);
				$sqlrequest = str_replace("[1]", $newposition,$sqlrequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Enregistrement des données d'une BLF
    function UtilisateursBLFUpdate($idblf,$type,$etiquette,$valeur) {
				$sqlrequest = str_replace("[0]", $idblf,$this->UtilisateursBLFUpdateSQLRequest);
				$sqlrequest = str_replace("[1]", $type,$sqlrequest);
				$sqlrequest = str_replace("[2]", $etiquette,$sqlrequest);
				$sqlrequest = str_replace("[3]", $valeur,$sqlrequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Ajout d'une BLF
    function UtilisateursBLFInsert($idutilisateur,$position,$type,$etiquette,$valeur) {
				$sqlrequest = str_replace("[0]", $idutilisateur,$this->UtilisateursBLFInsertSQLRequest);
				$sqlrequest = str_replace("[1]", $position,$sqlrequest);
				$sqlrequest = str_replace("[2]", $type,$sqlrequest);
				$sqlrequest = str_replace("[3]", $etiquette,$sqlrequest);
				$sqlrequest = str_replace("[4]", $valeur,$sqlrequest);
        $stmt = $this->pdo->prepare($sqlrequest);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*//Ajouter un client à partir d'un formulaire
    function AddClientRecovery($nom, $email, $telephone, $adresse, $partenaires_idpartenaires) {
        //préparation de la requête SQL
        $sql_clients = "INSERT INTO Clients (Nom, Email, Telephone, Adresse)
                        VALUES (:Nom, :Email, :Telephone, :Adresse, :Partenaires_idpartenaires";

        //préparation de la requete avec PDO
        $stmt_client = $this->pdo->prepare($sql_clients);

        //lier les paramètres aux valeurs provenant du formulaire
        $stmt_client->bindParam(":Nom", $nom, PDO::PARAM_STR);
        $stmt_client->bindParam(":Email", $email, PDO::PARAM_STR);
        $stmt_client->bindParam(":Telephone", $telephone, PDO::PARAM_INT);
        $stmt_client->bindParam(":Adresse", $adresse, PDO::PARAM_STR);
        $stmt_client->bindParam(":Partenares_idpartenaires", $partenaires_idpartenaires, PDO::PARAM_INT);

        try  {
            $result = $stmt_client->execute();
            if($result) {
                return true;
        } else {
            $errorInfo = $stmt_client->errorInfo();
            return "Erreur lors d l'insertion : " . $errorInfo[2];
        }
    } catch (PDOException $e) {
        return "Erreur PDO : ". $e->getMessage();
        }
    }

    //Traitement du formulaire d'ajout clients
    function processClientsForm($formData) {
        //validation des données
        $nom = htmlspecialchars($formData['Nom']);
        $email = htmlspecialchars($formData['Email']);
        $telephone = intval(preg_replace('/\D/', '', $formData['Telephone']));
        $adresse = htmlspecialchars($formData['Adresse']);
        $partenaires_idpartenaires = htmlspecialchars($formData['Partenaire_idpartenaires']);

        if (empty($nom) || empty($email) || empty($telephone) || empty($adresse) || empty($partenaires_idpartenaires)) {
            return "Veuillez remplir tous les champs obligatoires.";
   			}

        //Ajouter le partenaire
        return $this->AddClientRecovery($nom, $email, $telephone, $adresse, $partenaires_idpartenaires);
    }*/
}

// Instance de la class ShowClientForm
$UtilisateursForm = new ShowUtilisateursForm($pdo);


?>