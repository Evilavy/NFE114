package com.demo.model;

import jakarta.json.bind.annotation.JsonbProperty;
import jakarta.persistence.*;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

@Entity
@Table(name = "message")
public class Message {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;
    
    @Column(name = "trajet_id", nullable = false)
    private Long trajetId;
    
    @Column(name = "expediteur_id", nullable = false)
    private Long expediteurId;
    
    @Column(name = "destinataire_id", nullable = false)
    private Long destinataireId;
    
    @Column(name = "contenu", nullable = false)
    private String contenu;
    
    @Column(name = "date_envoi", nullable = false)
    private String dateEnvoi;
    
    @Column(name = "lu", nullable = false)
    private boolean lu;

    public Message() {
    }

    public Message(Long id, Long trajetId, Long expediteurId, Long destinataireId, String contenu, String dateEnvoi, boolean lu) {
        this.id = id;
        this.trajetId = trajetId;
        this.expediteurId = expediteurId;
        this.destinataireId = destinataireId;
        this.contenu = contenu;
        this.dateEnvoi = dateEnvoi;
        this.lu = lu;
    }

    @JsonbProperty("id")
    public Long getId() {
        return id;
    }

    public void setId(Long id) {
        this.id = id;
    }

    @JsonbProperty("trajetId")
    public Long getTrajetId() {
        return trajetId;
    }

    public void setTrajetId(Long trajetId) {
        this.trajetId = trajetId;
    }

    @JsonbProperty("expediteurId")
    public Long getExpediteurId() {
        return expediteurId;
    }

    public void setExpediteurId(Long expediteurId) {
        this.expediteurId = expediteurId;
    }

    @JsonbProperty("destinataireId")
    public Long getDestinataireId() {
        return destinataireId;
    }

    public void setDestinataireId(Long destinataireId) {
        this.destinataireId = destinataireId;
    }

    @JsonbProperty("contenu")
    public String getContenu() {
        return contenu;
    }

    public void setContenu(String contenu) {
        this.contenu = contenu;
    }

    @JsonbProperty("dateEnvoi")
    public String getDateEnvoi() {
        return dateEnvoi;
    }

    public void setDateEnvoi(String dateEnvoi) {
        this.dateEnvoi = dateEnvoi;
    }

    @JsonbProperty("lu")
    public boolean isLu() {
        return lu;
    }

    public void setLu(boolean lu) {
        this.lu = lu;
    }
} 