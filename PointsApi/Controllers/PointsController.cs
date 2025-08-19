using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using PointsApi.Data;
using PointsApi.Models;

namespace PointsApi.Controllers
{
    [ApiController]
    [Route("api/[controller]")]
    [AllowAnonymous]
    public class PointsController : ControllerBase
    {
        private readonly AppDbContext _context;
        public PointsController(AppDbContext context) => _context = context;

        [HttpGet("{id}")]
        public IActionResult GetPoints(int id)
        {
            var user = _context.Users.Find(id);
            if (user == null) return NotFound();
            return Ok(new { user.Id, user.Points });
        }

        [HttpPost("add")]
        public IActionResult AddPoints([FromBody] PointsOperation op)
        {
            var user = _context.Users.Find(op.Id);
            if (user == null) return NotFound();
            user.Points += op.Points;
            _context.SaveChanges();
            return Ok(new { user.Id, user.Points });
        }

        [HttpPost("remove")]
        public IActionResult RemovePoints([FromBody] PointsOperation op)
        {
            var user = _context.Users.Find(op.Id);
            if (user == null) return NotFound();
            if (user.Points < op.Points) return BadRequest("Not enough points");
            user.Points -= op.Points;
            _context.SaveChanges();
            return Ok(new { user.Id, user.Points });
        }
    }

    public class PointsOperation
    {
        public int Id { get; set; }
        public int Points { get; set; }
    }
}