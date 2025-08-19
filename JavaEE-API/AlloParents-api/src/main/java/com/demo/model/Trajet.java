package com.demo.model;

import jakarta.json.bind.annotation.JsonbProperty;
import jakarta.persistence.*;
import java.util.List;
import java.util.ArrayList;

@Entity
@Table(name = "trajet")
public class Trajet {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    
    @Column(name = "point_depart", nullable = false)
    private String pointDepart;
    
    @Column(name = "date_depart", nullable = false)
    private String dateDepart;
    
    @Column(name = "heure_depart", nullable = false)
    private String heureDepart;
    
    @Column(name = "date_arrivee", nullable = false)
    private String dateArrivee;
    
    @Column(name = "heure_arrivee", nullable = false)
    private String heureArrivee;
    
    @Column(name = "nombre_places", nullable = false)
    private Integer nombrePlaces;
    
    @Column(name = "conducteur_id", nullable = false)
    private Long conducteurId;
    
    @Column(name = "voiture_id", nullable = false)
    private Long voitureId;
    
    @Column(name = "statut", nullable = false)
    private String statut; // "disponible", "en_cours", "termine", "annule"
    
    @Column(name = "description")
    private String description;
    
    @Column(name = "cout_points", nullable = false)
    private Integer pointsCout;
    
    @ElementCollection
    @CollectionTable(name = "trajet_enfant", joinColumns = @JoinColumn(name = "trajet_id"))
    @Column(name = "enfant_id")
    private List<Long> enfantsIds; // IDs des enfants associés au trajet
    
    @Column(name = "duree_minutes")
    private Integer dureeMinutes; // Durée du trajet en minutes
    
    @Column(name = "distance_km")
    private Double distanceKm; // Distance du trajet en kilomètres
    
    @Column(name = "ecole_arrivee_id")
    private Long ecoleArriveeId; // ID de l'école de destination

    public Trajet() {
        this.enfantsIds = new ArrayList<>();
        this.pointsCout = 5; // Coût par défaut de 5 points
    }

    public Trajet(Long id, String pointDepart, String dateDepart, String heureDepart, 
                  String dateArrivee, String heureArrivee, Integer nombrePlaces, Long conducteurId, Long voitureId, 
                  String statut, String description, Integer pointsCout, List<Long> enfantsIds) {
        this.id = id;
        this.pointDepart = pointDepart;
        this.dateDepart = dateDepart;
        this.heureDepart = heureDepart;
        this.dateArrivee = dateArrivee;
        this.heureArrivee = heureArrivee;
        this.nombrePlaces = nombrePlaces;
        this.conducteurId = conducteurId;
        this.voitureId = voitureId;
        this.statut = statut;
        this.description = description;
        this.pointsCout = pointsCout;
        setEnfantsIds(enfantsIds);
    }

    @JsonbProperty("id")
    public Long getId() {
        return id;
    }

    public void setId(Long id) {
        this.id = id;
    }

    @JsonbProperty("pointDepart")
    public String getPointDepart() {
        return pointDepart;
    }

    public void setPointDepart(String pointDepart) {
        this.pointDepart = pointDepart;
    }

    @JsonbProperty("dateDepart")
    public String getDateDepart() {
        return dateDepart;
    }

    public void setDateDepart(String dateDepart) {
        this.dateDepart = dateDepart;
    }

    @JsonbProperty("heureDepart")
    public String getHeureDepart() {
        return heureDepart;
    }

    public void setHeureDepart(String heureDepart) {
        this.heureDepart = heureDepart;
    }

    @JsonbProperty("dateArrivee")
    public String getDateArrivee() {
        return dateArrivee;
    }

    public void setDateArrivee(String dateArrivee) {
        this.dateArrivee = dateArrivee;
    }

    @JsonbProperty("heureArrivee")
    public String getHeureArrivee() {
        return heureArrivee;
    }

    public void setHeureArrivee(String heureArrivee) {
        this.heureArrivee = heureArrivee;
    }

    @JsonbProperty("nombrePlaces")
    public Integer getNombrePlaces() {
        return nombrePlaces;
    }

    public void setNombrePlaces(Integer nombrePlaces) {
        this.nombrePlaces = nombrePlaces;
    }

    @JsonbProperty("conducteurId")
    public Long getConducteurId() {
        return conducteurId;
    }

    public void setConducteurId(Long conducteurId) {
        this.conducteurId = conducteurId;
    }

    @JsonbProperty("voitureId")
    public Long getVoitureId() {
        return voitureId;
    }

    public void setVoitureId(Long voitureId) {
        this.voitureId = voitureId;
    }

    @JsonbProperty("statut")
    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    @JsonbProperty("description")
    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    @JsonbProperty("pointsCout")
    public Integer getPointsCout() {
        return pointsCout;
    }

    public void setPointsCout(Integer pointsCout) {
        this.pointsCout = pointsCout;
    }

    @JsonbProperty("enfantsIds")
    public List<Long> getEnfantsIds() {
        if (this.enfantsIds == null) {
            this.enfantsIds = new ArrayList<>();
        }
        return this.enfantsIds;
    }

    public void setEnfantsIds(List<Long> enfantsIds) {
        this.enfantsIds = (enfantsIds != null) ? enfantsIds : new ArrayList<>();
    }



    @JsonbProperty("dureeMinutes")
    public Integer getDureeMinutes() {
        return dureeMinutes;
    }

    public void setDureeMinutes(Integer dureeMinutes) {
        this.dureeMinutes = dureeMinutes;
    }

    @JsonbProperty("distanceKm")
    public Double getDistanceKm() {
        return distanceKm;
    }

    public void setDistanceKm(Double distanceKm) {
        this.distanceKm = distanceKm;
    }

    @JsonbProperty("ecoleArriveeId")
    public Long getEcoleArriveeId() {
        return ecoleArriveeId;
    }

    public void setEcoleArriveeId(Long ecoleArriveeId) {
        this.ecoleArriveeId = ecoleArriveeId;
    }
} 