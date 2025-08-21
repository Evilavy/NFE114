package com.demo;

import com.demo.model.Trajet;
import com.demo.model.User;
import com.demo.service.EntityManagerService;
import jakarta.persistence.EntityManager;
import jakarta.persistence.TypedQuery;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Path("/trajets")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class TrajetResource {
    
    private EntityManager em = EntityManagerService.getEntityManagerFactory().createEntityManager();
    
    @GET
    public List<Trajet> getAllTrajets() {
        try {
            TypedQuery<Trajet> query = em.createQuery("SELECT t FROM Trajet t", Trajet.class);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des trajets: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    @GET
    @Path("/{id}")
    public Response getTrajet(@PathParam("id") Long id) {
        try {
            Trajet trajet = em.find(Trajet.class, id);
            if (trajet != null) {
                return Response.ok(trajet).build();
            } else {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("Trajet non trouvé avec l'ID: " + id)
                        .build();
            }
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération du trajet: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @POST
    public Response createTrajet(Trajet trajet) {
        if (trajet.getPointDepart() == null || trajet.getPointDepart().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Le point de départ est obligatoire")
                    .build();
        }

        if (trajet.getDateDepart() == null || trajet.getDateDepart().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("La date de départ est obligatoire")
                    .build();
        }
        if (trajet.getHeureDepart() == null || trajet.getHeureDepart().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("L'heure de départ est obligatoire")
                    .build();
        }
        if (trajet.getDateArrivee() == null || trajet.getDateArrivee().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("La date d'arrivée est obligatoire")
                    .build();
        }
        if (trajet.getHeureArrivee() == null || trajet.getHeureArrivee().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("L'heure d'arrivée est obligatoire")
                    .build();
        }
        if (trajet.getNombrePlaces() == null || trajet.getNombrePlaces() < 0) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Le nombre de places doit être positif ou nul")
                    .build();
        }
        if (trajet.getConducteurId() == null) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("L'ID du conducteur est obligatoire")
                    .build();
        }
        if (trajet.getVoitureId() == null) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("L'ID de la voiture est obligatoire")
                    .build();
        }
        if (trajet.getPointsCout() == null || trajet.getPointsCout() < 0) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Le coût en points doit être positif ou nul")
                    .build();
        }

        
        // Validation logique des dates et heures
        if (trajet.getDateDepart().compareTo(trajet.getDateArrivee()) > 0) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("La date d'arrivée doit être après la date de départ")
                    .build();
        }
        if (trajet.getDateDepart().equals(trajet.getDateArrivee()) && 
            trajet.getHeureDepart().compareTo(trajet.getHeureArrivee()) >= 0) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("L'heure d'arrivée doit être après l'heure de départ")
                    .build();
        }
        
        // Validation que le trajet n'est pas dans le passé
        try {
            String dateTimeDepart = trajet.getDateDepart() + " " + trajet.getHeureDepart();
            java.time.format.DateTimeFormatter formatter = java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm");
            java.time.LocalDateTime departDateTime = java.time.LocalDateTime.parse(dateTimeDepart, formatter);
            java.time.LocalDateTime now = java.time.LocalDateTime.now();
            
            if (departDateTime.isBefore(now)) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Impossible de créer un trajet avec une date/heure de départ dans le passé")
                        .build();
            }
        } catch (Exception e) {
            return Response.status(Response.Status.BAD_REQUEST)
                    .entity("Format de date/heure invalide")
                    .build();
        }
        
        try {
            if (!em.isOpen()) {
                em = EntityManagerService.getEntityManagerFactory().createEntityManager();
            }
            em.getTransaction().begin();
            
            if (trajet.getStatut() == null) {
                trajet.setStatut("disponible");
            }
            if (trajet.getDescription() == null) {
                trajet.setDescription("");
            }
            if (trajet.getEnfantsIds() == null) {
                trajet.setEnfantsIds(new ArrayList<>());
            }
            
            em.persist(trajet);
            em.getTransaction().commit();
            
            return Response.status(Response.Status.CREATED).entity(trajet).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la création du trajet: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @POST
    @Path("/{id}/reserver")
    @Consumes(MediaType.WILDCARD)
    public Response reserverTrajet(@PathParam("id") Long trajetId, @QueryParam("userId") Long userId, @QueryParam("enfantId") Long enfantId) {
        try {
            em.getTransaction().begin();
            
            // Trouver le trajet
            Trajet trajet = em.find(Trajet.class, trajetId);
            
            if (trajet == null) {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("Trajet non trouvé")
                        .build();
            }
            
            if (!"disponible".equals(trajet.getStatut())) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Ce trajet n'est plus disponible")
                        .build();
            }
            
            // Vérifier s'il y a des places disponibles
            int nombreEnfantsActuels = trajet.getEnfantsIds() != null ? trajet.getEnfantsIds().size() : 0;
            int placesDisponibles = trajet.getNombrePlaces() - nombreEnfantsActuels;
            
            if (placesDisponibles <= 0) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Aucune place disponible sur ce trajet")
                        .build();
            }
            
            // Vérifier que l'utilisateur a assez de points
            // TODO: Récupérer l'utilisateur depuis UserResource et vérifier ses points
            // Pour l'instant, on simule que l'utilisateur a assez de points
            
            // Retirer les points à l'utilisateur (5 points par défaut)
            int pointsCout = trajet.getPointsCout() != null ? trajet.getPointsCout() : 5;
            
            // Ajouter les points au conducteur
            // TODO: Ajouter les points au conducteur via UserResource

            // Ajouter l'enfant à la liste des enfants du trajet
            if (enfantId != null) {
                List<Long> enfantsIds = trajet.getEnfantsIds();
                if (enfantsIds == null) {
                    enfantsIds = new ArrayList<>();
                }
                if (!enfantsIds.contains(enfantId)) {
                    enfantsIds.add(enfantId);
                    trajet.setEnfantsIds(enfantsIds);
                }
            }
            
            // Vérifier si le trajet devient complet après cette réservation
            if (nombreEnfantsActuels + 1 >= trajet.getNombrePlaces()) {
                trajet.setStatut("complet");
            }
            
            em.merge(trajet);
            em.getTransaction().commit();
            
            return Response.ok(trajet).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la réservation du trajet: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @POST
    @Path("/{id}/reserver-multiple")
    @Consumes(MediaType.APPLICATION_JSON)
    public Response reserverTrajetMultiple(@PathParam("id") Long trajetId, Map<String, Object> requestData) {
        try {
            em.getTransaction().begin();
            
            // Trouver le trajet
            Trajet trajet = em.find(Trajet.class, trajetId);
            
            if (trajet == null) {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("Trajet non trouvé")
                        .build();
            }
            
            if (!"disponible".equals(trajet.getStatut())) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Ce trajet n'est plus disponible")
                        .build();
            }
            
            // Récupérer les données de la requête
            Long userId = Long.valueOf(requestData.get("userId").toString());
            @SuppressWarnings("unchecked")
            List<Long> enfantsIds = (List<Long>) requestData.get("enfantsIds");
            int pointsCout = trajet.getPointsCout() != null ? trajet.getPointsCout() : 5;
            
            if (enfantsIds == null || enfantsIds.isEmpty()) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Aucun enfant sélectionné")
                        .build();
            }
            
            // Vérifier qu'il y a assez de places disponibles
            int nombreEnfantsDemandes = enfantsIds.size();
            int nombreEnfantsActuels = trajet.getEnfantsIds() != null ? trajet.getEnfantsIds().size() : 0;
            int placesDisponibles = trajet.getNombrePlaces() - nombreEnfantsActuels;
            
            if (nombreEnfantsDemandes > placesDisponibles) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Pas assez de places disponibles. Demandé: " + nombreEnfantsDemandes + ", Disponible: " + placesDisponibles)
                        .build();
            }
            
            // Vérifier que l'utilisateur a assez de points
            // TODO: Récupérer l'utilisateur depuis UserResource et vérifier ses points
            // Pour l'instant, on simule que l'utilisateur a assez de points
            
            // Calculer le coût total
            int coutTotal = pointsCout * nombreEnfantsDemandes;
            
            // Ajouter les enfants à la liste des enfants du trajet
            List<Long> enfantsActuels = trajet.getEnfantsIds();
            if (enfantsActuels == null) {
                enfantsActuels = new ArrayList<>();
            }
            
            // Vérifier que les enfants ne sont pas déjà dans le trajet
            for (Long enfantId : enfantsIds) {
                if (enfantsActuels.contains(enfantId)) {
                    return Response.status(Response.Status.BAD_REQUEST)
                            .entity("L'enfant avec l'ID " + enfantId + " est déjà inscrit à ce trajet")
                            .build();
                }
            }
            
            // Ajouter tous les enfants
            enfantsActuels.addAll(enfantsIds);
            trajet.setEnfantsIds(enfantsActuels);
            
            // Vérifier si le trajet devient complet après cette réservation
            if (nombreEnfantsActuels + nombreEnfantsDemandes >= trajet.getNombrePlaces()) {
                trajet.setStatut("complet");
            }
            
            em.merge(trajet);
            em.getTransaction().commit();
            
            // Retourner les informations de la réservation
            Map<String, Object> response = new HashMap<>();
            response.put("trajet", trajet);
            response.put("enfantsReserves", nombreEnfantsDemandes);
            response.put("coutTotal", coutTotal);
            response.put("placesRestantes", trajet.getNombrePlaces());
            
            return Response.ok(response).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la réservation multiple du trajet: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @POST
    @Path("/{id}/annuler-reservation")
    @Consumes(MediaType.WILDCARD)
    public Response annulerReservation(@PathParam("id") Long trajetId, @QueryParam("userId") Long userId, @QueryParam("enfantId") Long enfantId) {
        try {
            em.getTransaction().begin();
            
            // Trouver le trajet
            Trajet trajet = em.find(Trajet.class, trajetId);
            
            if (trajet == null) {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("Trajet non trouvé")
                        .build();
            }
            
            // Vérifier si l'enfant est bien dans le trajet
            List<Long> enfantsIds = trajet.getEnfantsIds();
            if (enfantsIds == null || !enfantsIds.contains(enfantId)) {
                return Response.status(Response.Status.BAD_REQUEST)
                        .entity("Cet enfant n'est pas inscrit à ce trajet")
                        .build();
            }
            
            // Retirer l'enfant de la liste
            enfantsIds.remove(enfantId);
            trajet.setEnfantsIds(enfantsIds);
            
            // Si le trajet était complet, le remettre en disponible
            if ("complet".equals(trajet.getStatut())) {
                trajet.setStatut("disponible");
            }
            
            em.merge(trajet);
            em.getTransaction().commit();
            
            return Response.ok()
                    .entity("Réservation annulée avec succès")
                    .build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de l'annulation de la réservation: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @PUT
    @Path("/{id}")
    public Response updateTrajet(@PathParam("id") Long id, Trajet trajetUpdate) {
        try {
            em.getTransaction().begin();
            
            Trajet trajet = em.find(Trajet.class, id);
            if (trajet != null) {
                if (trajetUpdate.getPointDepart() != null) {
                    trajet.setPointDepart(trajetUpdate.getPointDepart());
                }

                if (trajetUpdate.getDateDepart() != null) {
                    trajet.setDateDepart(trajetUpdate.getDateDepart());
                }
                if (trajetUpdate.getHeureDepart() != null) {
                    trajet.setHeureDepart(trajetUpdate.getHeureDepart());
                }
                if (trajetUpdate.getDateArrivee() != null) {
                    trajet.setDateArrivee(trajetUpdate.getDateArrivee());
                }
                if (trajetUpdate.getHeureArrivee() != null) {
                    trajet.setHeureArrivee(trajetUpdate.getHeureArrivee());
                }
                if (trajetUpdate.getNombrePlaces() != null) {
                    trajet.setNombrePlaces(trajetUpdate.getNombrePlaces());
                }
                if (trajetUpdate.getConducteurId() != null) {
                    trajet.setConducteurId(trajetUpdate.getConducteurId());
                }
                if (trajetUpdate.getVoitureId() != null) {
                    trajet.setVoitureId(trajetUpdate.getVoitureId());
                }
                if (trajetUpdate.getStatut() != null) {
                    trajet.setStatut(trajetUpdate.getStatut());
                }
                if (trajetUpdate.getDescription() != null) {
                    trajet.setDescription(trajetUpdate.getDescription());
                }
                if (trajetUpdate.getPointsCout() != null) {
                    trajet.setPointsCout(trajetUpdate.getPointsCout());
                }
                if (trajetUpdate.getEnfantsIds() != null) {
                    trajet.setEnfantsIds(trajetUpdate.getEnfantsIds());
                }
                
                em.merge(trajet);
                em.getTransaction().commit();
                return Response.ok(trajet).build();
            } else {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("Trajet non trouvé avec l'ID: " + id)
                        .build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la mise à jour du trajet: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @DELETE
    @Path("/{id}")
    public Response deleteTrajet(@PathParam("id") Long id) {
        try {
            em.getTransaction().begin();
            
            Trajet trajet = em.find(Trajet.class, id);
            if (trajet != null) {
                em.remove(trajet);
                em.getTransaction().commit();
                return Response.status(Response.Status.NO_CONTENT).build();
            } else {
                return Response.status(Response.Status.NOT_FOUND)
                        .entity("Trajet non trouvé avec l'ID: " + id)
                        .build();
            }
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la suppression du trajet: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
    
    @GET
    @Path("/conducteur/{conducteurId}")
    public List<Trajet> getTrajetsByConducteur(@PathParam("conducteurId") Long conducteurId) {
        try {
            TypedQuery<Trajet> query = em.createQuery(
                "SELECT t FROM Trajet t WHERE t.conducteurId = :conducteurId", Trajet.class);
            query.setParameter("conducteurId", conducteurId);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des trajets par conducteur: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    @GET
    @Path("/statut/{statut}")
    public List<Trajet> getTrajetsByStatut(@PathParam("statut") String statut) {
        try {
            TypedQuery<Trajet> query = em.createQuery(
                "SELECT t FROM Trajet t WHERE t.statut = :statut", Trajet.class);
            query.setParameter("statut", statut);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des trajets par statut: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    @GET
    @Path("/enfant/{enfantId}")
    public List<Trajet> getTrajetsByEnfant(@PathParam("enfantId") Long enfantId) {
        try {
            // Note: Cette requête nécessite une implémentation spéciale pour JSON
            // Pour l'instant, on récupère tous les trajets et on filtre côté application
            TypedQuery<Trajet> query = em.createQuery("SELECT t FROM Trajet t", Trajet.class);
            List<Trajet> allTrajets = query.getResultList();
            List<Trajet> trajetsEnfant = new ArrayList<>();
            
            for (Trajet trajet : allTrajets) {
                if (trajet.getEnfantsIds() != null && trajet.getEnfantsIds().contains(enfantId)) {
                    trajetsEnfant.add(trajet);
                }
            }
            return trajetsEnfant;
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des trajets par enfant: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    @GET
    @Path("/voiture/{voitureId}")
    public List<Trajet> getTrajetsByVoiture(@PathParam("voitureId") Long voitureId) {
        try {
            TypedQuery<Trajet> query = em.createQuery(
                "SELECT t FROM Trajet t WHERE t.voitureId = :voitureId", Trajet.class);
            query.setParameter("voitureId", voitureId);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des trajets par voiture: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    @GET
    @Path("/user/{userId}/reservations")
    public List<Trajet> getReservationsByUser(@PathParam("userId") Long userId) {
        try {
            // Récupérer tous les trajets et filtrer ceux où l'utilisateur a des enfants inscrits
            TypedQuery<Trajet> query = em.createQuery("SELECT t FROM Trajet t", Trajet.class);
            List<Trajet> allTrajets = query.getResultList();
            List<Trajet> userReservations = new ArrayList<>();
            
            for (Trajet trajet : allTrajets) {
                // Vérifier que l'utilisateur a des enfants inscrits dans ce trajet
                if (trajet.getEnfantsIds() != null && !trajet.getEnfantsIds().isEmpty()) {
                    // IMPORTANT : Ne retourner que les trajets où l'utilisateur est PASSAGER
                    // (c'est-à-dire qu'il a des enfants inscrits mais n'est PAS le conducteur)
                    if (!userId.equals(trajet.getConducteurId())) {
                        userReservations.add(trajet);
                    }
                }
            }
            
            return userReservations;
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des réservations de l'utilisateur: " + e.getMessage());
            return new ArrayList<>();
        }
    }
} 