package com.demo;

import com.demo.model.Message;
import com.demo.service.EntityManagerService;
import jakarta.persistence.EntityManager;
import jakarta.persistence.TypedQuery;
import jakarta.ws.rs.*;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;
import java.util.ArrayList;
import java.util.List;

@Path("/messages")
@Produces(MediaType.APPLICATION_JSON)
@Consumes(MediaType.APPLICATION_JSON)
public class MessageResource {

    private EntityManager em = EntityManagerService.getEntityManagerFactory().createEntityManager();

    @GET
    @Path("/user/{userId}")
    public List<Message> getMessagesForUser(@PathParam("userId") Long userId) {
        try {
            TypedQuery<Message> query = em.createQuery(
                "SELECT m FROM Message m WHERE m.expediteurId = :uid OR m.destinataireId = :uid ORDER BY m.dateEnvoi ASC",
                Message.class
            );
            query.setParameter("uid", userId);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération des messages utilisateur: " + e.getMessage());
            return new ArrayList<>();
        }
    }

    @GET
    @Path("/conversation/{trajetId}/{userId}/{destinataireId}")
    public List<Message> getConversation(
            @PathParam("trajetId") Long trajetId,
            @PathParam("userId") Long userId,
            @PathParam("destinataireId") Long destinataireId
    ) {
        try {
            TypedQuery<Message> query = em.createQuery(
                "SELECT m FROM Message m WHERE m.trajetId = :tid AND ((m.expediteurId = :u AND m.destinataireId = :d) OR (m.expediteurId = :d AND m.destinataireId = :u)) ORDER BY m.dateEnvoi ASC",
                Message.class
            );
            query.setParameter("tid", trajetId);
            query.setParameter("u", userId);
            query.setParameter("d", destinataireId);
            return query.getResultList();
        } catch (Exception e) {
            System.err.println("Erreur lors de la récupération de la conversation: " + e.getMessage());
            return new ArrayList<>();
        }
    }

    @POST
    public Response createMessage(Message message) {
        // Validations
        if (message.getTrajetId() == null) {
            return Response.status(Response.Status.BAD_REQUEST).entity("Le trajetId est obligatoire").build();
        }
        if (message.getExpediteurId() == null || message.getDestinataireId() == null) {
            return Response.status(Response.Status.BAD_REQUEST).entity("Les identifiants d'expéditeur et de destinataire sont obligatoires").build();
        }
        if (message.getContenu() == null || message.getContenu().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST).entity("Le contenu du message est obligatoire").build();
        }
        if (message.getDateEnvoi() == null || message.getDateEnvoi().trim().isEmpty()) {
            return Response.status(Response.Status.BAD_REQUEST).entity("La date d'envoi est obligatoire").build();
        }

        try {
            if (!em.isOpen()) {
                em = EntityManagerService.getEntityManagerFactory().createEntityManager();
            }
            em.getTransaction().begin();
            if (!message.isLu()) {
                message.setLu(false);
            }
            em.persist(message);
            em.getTransaction().commit();
            return Response.status(Response.Status.CREATED).entity(message).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors de la création du message: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }

    @PUT
    @Path("/{id}/lu")
    public Response markAsRead(@PathParam("id") Long id) {
        try {
            if (!em.isOpen()) {
                em = EntityManagerService.getEntityManagerFactory().createEntityManager();
            }
            em.getTransaction().begin();
            Message msg = em.find(Message.class, id);
            if (msg == null) {
                return Response.status(Response.Status.NOT_FOUND).entity("Message non trouvé").build();
            }
            msg.setLu(true);
            em.merge(msg);
            em.getTransaction().commit();
            return Response.ok(msg).build();
        } catch (Exception e) {
            if (em.getTransaction().isActive()) {
                em.getTransaction().rollback();
            }
            System.err.println("Erreur lors du marquage du message comme lu: " + e.getMessage());
            return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
        }
    }
}


